document.addEventListener('DOMContentLoaded', function() {
    // Inicializa tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Função para copiar link
    function handleCopyLink(elementId) {
        const copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999); // Para dispositivos móveis
        try {
            navigator.clipboard.writeText(copyText.value);
            showToast('Link copiado para a área de transferência!', 'success');
        } catch (err) {
            console.error('Falha ao copiar texto: ', err);
            showToast('Erro ao copiar link', 'error');
        }
    }

    // Configuração dos Toasts (Bootstrap)
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    const toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl);
    });

    // Função para exibir toast
    function showToast(message, type = 'success') {
        const toast = new bootstrap.Toast(document.getElementById('copyToast'));
        const toastBody = document.getElementById('toastBody');
        toastBody.textContent = message;
        $('#copyToast').removeClass('bg-success bg-error').addClass(`bg-${type}`);
        toast.show();
    }

    // Delegation para botões de copiar link
    $(document).on('click', '.copy-coupon-link', function() {
        const couponId = $(this).data('coupon-id');
        handleCopyLink(`coupon-link-${couponId}`);
    });

    // Função para confirmar exclusão de cupom
    $(document).on('click', '.delete-coupon', function(e) {
        e.preventDefault();
        const couponId = $(this).data('id');
        const couponCode = $(this).data('code');
        Swal.fire({
            title: 'Tem certeza?',
            text: `Você está prestes a excluir o cupom ${couponCode}. Esta ação não pode ser desfeita!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/coupons/${couponId}`,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Excluído!',
                                'O cupom foi excluído com sucesso.',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Erro!',
                            'Ocorreu um erro ao tentar excluir o cupom.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Função para copiar o código do cupom
    $(document).on('click', '.copy-coupon-code', function() {
        const code = $(this).data('code');
        navigator.clipboard.writeText(code).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Código copiado!',
                text: `O código ${code} foi copiado para a área de transferência.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }, function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro ao copiar',
                text: 'Não foi possível copiar o código do cupom.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        });
    });

    // Função para mostrar detalhes do cupom
    $(document).on('click', '.show-coupon-details', function() {
        const couponId = $(this).data('id');
        $.ajax({
            url: `/coupons/${couponId}/details`,
            type: 'GET',
            success: function(response) {
                $('#couponDetailsModal .modal-body').html(response);
                $('#couponDetailsModal').modal('show');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Não foi possível carregar os detalhes do cupom.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        });
    });

    // Atualiza o status do cupom em tempo real
    function updateCouponStatus() {
        $('.coupon-item').each(function() {
            const expiresAt = $(this).data('expires-at');
            const usageLimit = $(this).data('usage-limit');
            const timesUsed = $(this).data('times-used');
            if (expiresAt && new Date(expiresAt) < new Date()) {
                $(this).find('.coupon-status')
                    .removeClass('badge-success')
                    .addClass('badge-secondary')
                    .text('Expirado');
            } else if (usageLimit && timesUsed >= usageLimit) {
                $(this).find('.coupon-status')
                    .removeClass('badge-success')
                    .addClass('badge-secondary')
                    .text('Limite atingido');
            }
        });
    }
    setInterval(updateCouponStatus, 60000);

    // Lógica de desconto: campos para percentual e valor fixo
    const discountVisible = document.getElementById('discount_percent_visible');
    const discountHidden = document.getElementById('discount_percent');
    const amountOffVisible = document.getElementById('amount_off_visible');
    const amountOffHidden = document.getElementById('amount_off');
    const discountTypeSelect = document.getElementById('discount_type');

    function updateDiscountPercent() {
        const value = parseFloat(discountVisible.value);
        if (!isNaN(value)) {
            discountHidden.value = (value / 100).toFixed(2);
        } else {
            discountHidden.value = '';
        }
    }
    discountVisible.addEventListener('input', updateDiscountPercent);
    updateDiscountPercent();

    function updateAmountOff() {
        const value = parseFloat(amountOffVisible.value);
        if (!isNaN(value)) {
            amountOffHidden.value = Math.round(value * 100);
        } else {
            amountOffHidden.value = '';
        }
    }
    amountOffVisible.addEventListener('input', updateAmountOff);
    updateAmountOff();

    function toggleDiscountFields() {
        const type = discountTypeSelect.value;
        if (type === 'percent') {
            document.getElementById('discount_percent_div').style.display = 'block';
            document.getElementById('amount_off_div').style.display = 'none';
            discountVisible.required = true;
            amountOffVisible.required = false;
        } else if (type === 'fixed') {
            document.getElementById('discount_percent_div').style.display = 'none';
            document.getElementById('amount_off_div').style.display = 'block';
            discountVisible.required = false;
            amountOffVisible.required = true;
        }
    }
    discountTypeSelect.addEventListener('change', toggleDiscountFields);
    toggleDiscountFields();

    // Lógica para exibir/ocultar campos de expiração
    const expirationSelect = document.getElementById('expiration_type');
    const usageLimitGroup = document.getElementById('usage_limit_group');
    const expiresAtGroup = document.getElementById('expires_at_group');
    const usageLimitInput = document.getElementById('usage_limit');

    function toggleExpirationFields() {
        $('#usage_limit, #expires_at').prop('required', false);
        $('#usage_limit_group, #expires_at_group').hide();
        const value = expirationSelect.value;
        if (value === 'usage') {
            $('#usage_limit_group').show();
            $('#usage_limit').prop('required', true);
            // Define data padrão para expiração: 31/12/2999
            $('#expires_at').val('2999-12-31');
        } else if (value === 'date') {
            $('#expires_at_group').show();
            $('#expires_at').prop('required', true);
        }
    }
    expirationSelect.addEventListener('change', toggleExpirationFields);
    toggleExpirationFields();

    // Validação do formato do código (converte para maiúsculo e remove caracteres inválidos)
    $('#code').on('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-_]/g, '');
    });
});