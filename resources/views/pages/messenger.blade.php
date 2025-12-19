@extends('layouts.user-no-nav')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="user-id" content="{{ auth()->id() }}">

@section('page_title', __('Messenger'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/libs/@selectize/selectize/dist/css/selectize.css',
            '/libs/@selectize/selectize/dist/css/selectize.bootstrap4.css',
            '/libs/dropzone/dist/dropzone.css',
            '/libs/photoswipe/dist/photoswipe.css',
            '/libs/photoswipe/dist/default-skin/default-skin.css',
            '/css/pages/messenger.css',
            '/css/pages/checkout.css'
         ])->withFullUrl()
    !!}
    <style>
        .conversations-list .pl-3 {
            padding-left: 0 !important;
        }
        @media (max-width: 390px) {
            .d-flex span {
                font-size: 14px; /* Tamanho da fonte ajustado para telas menores */
            }
        }
        @media (max-width: 349px) {
            .d-flex span {
                font-size: 12px; /* Tamanho da fonte ajustado para telas menores */
            }
        }
        @media (max-width: 299px) {
            .d-flex span {
                font-size: 10px; /* Tamanho da fonte ajustado para telas menores */
            }
        }
        @media (max-width: 259px) {
            .d-flex span {
                font-size: 8px; /* Tamanho da fonte ajustado para telas menores */
            }
        }

        .message-price-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 5px;
        }

        .message-price-time {
            color: #999;
            font-size: 12px;
            margin-right: 8px;
        }

        .message-price {
            color: #555;
            font-size: 14px;
        }

        .payment-status-icon {
            color: green;
            margin-left: 5px;
        }

    </style>
    
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/messenger/messenger.js',
            '/js/messenger/elements.js',
            '/libs/@selectize/selectize/dist/js/standalone/selectize.min.js',
            '/libs/dropzone/dist/dropzone.js',
            '/js/FileUpload.js',
            '/js/plugins/media/photoswipe.js',
            '/libs/photoswipe/dist/photoswipe-ui-default.min.js',
            '/js/plugins/media/mediaswipe.js',
            '/js/plugins/media/mediaswipe-loader.js',
            '/libs/@joeattardi/emoji-button/dist/index.js',
            '/js/pages/lists.js',
            '/js/pages/checkout.js',
            '/libs/pusher-js-auth/lib/pusher-auth.js'
         ])->withFullUrl()
    !!}

<script>
    // Verifica se os dados de pagamento da Suitpay estão definidos na sessão e exibe o modal do código QR da Suitpay
    @if (Session::has('suitpay_payment_data') && Session::get('suitpay_payment_data')['user_id'] == Auth::user()->id)
        $(document).ready(function() {
            // Função para fechar o modal e recarregar a página
            function closeAndReload() {
                $('#suitpayQrcodeModal').modal('hide');
                location.reload(true); // Use 'true' para recarregar a página do servidor
            }

            // Verifica se o modal já foi exibido anteriormente
            if (localStorage.getItem('suitpayModalDisplayed') !== 'true') {
                $('#suitpayQrcodeModal').modal('show');

                // Adiciona um ouvinte de eventos para recarregar a página quando o modal for fechado manualmente
                $('#suitpayQrcodeModal').on('hidden.bs.modal', function (e) {
                    // Destrói a sessão e fecha o modal manualmente
                    $.ajax({
                        url: '/suitpay/destroy-session',
                        type: 'POST',
                        data: {
                            _token: '{{csrf_token()}}'
                        },
                        success: function(response) {
                            console.log(response);
                            closeAndReload();
                        },
                        error: function() {
                            closeAndReload();
                        }
                    });
                });

                // Oculta o modal após 30 segundos e destrói a sessão
                setTimeout(function() {
                    $.ajax({
                        url: '/suitpay/destroy-session',
                        type: 'POST',
                        data: {
                            _token: '{{csrf_token()}}'
                        },
                        success: function(response) {
                            console.log(response);
                            // Verifica se o pagamento foi bem-sucedido
                            if (response.status === 'PAID_OUT') {
                                // Define o indicador de que o modal foi exibido para evitar mostrá-lo novamente
                                localStorage.setItem('suitpayModalDisplayed', 'true');
                            }
                            // Fecha o modal após a verificação do pagamento, mesmo que não seja bem-sucedido
                            closeAndReload();
                        },
                        error: function() {
                            // Em caso de erro na solicitação AJAX, também fecha o modal e recarrega a página
                            closeAndReload();
                        }
                    });
                }, 30000);
            }
        });
    @endif

    // Adiciona um evento ao botão "Fechar"
    document.getElementById('closeModalButton').addEventListener('click', function() {
        // Recarrega a página ao fechar o modal
        location.reload();
    });
</script>

@stop

@section('content')
    @include('elements.uploaded-file-preview-template')
    @include('elements.photoswipe-container')
    @include('elements.report-user-or-post',['reportStatuses' => ListsHelper::getReportTypes()])
    @include('elements.feed.post-delete-dialog')
    @include('elements.feed.post-list-management')
    @include('elements.messenger.message-price-dialog')
    @include('elements.checkout.checkout-box')
    @include('elements.attachments-uploading-dialog')
    @include('elements.messenger.locked-message-no-attachments-dialog')
    <div class="row">
        <div class="min-vh-100 col-12">
            <div class="container messenger min-vh-100">
                <div class="row min-vh-100">
                    <div class="col-3 col-xl-3 col-lg-3 col-md-3 col-sm-3 col-xs-2 border border-right-0 border-left-0 rounded-left conversations-wrapper min-vh-100 overflow-hidden border-top ">
                        <div class="d-flex justify-content-center justify-content-md-between pt-3 pr-1 pb-2">
                            <h5 class="d-none d-md-block text-truncate pl-3 pl-md-0 text-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{__('Contacts')}}</h5>
                            <span data-toggle="tooltip" title="" class="pointer-cursor"
                                  @if(!count($availableContacts))
                                    data-original-title="{{trans_choice('Before sending a new message, please subscribe to a creator a follow a free profile.',['user' => 0])}}"
                                  @else
                                    data-original-title="{{trans_choice('Send a new message',['user' => 0])}}"
                                  @endif
                            >
                                <a title="" class="pointer-cursor new-conversation-toggle" data-original-title="{{trans_choice('Send a new message',['user' => 0])}}">  
                                    <div class="mt-0 h5">
                                        @include('elements.icon',['icon'=>'create-outline','variant'=>'medium'])

                                    </div> 
                                </a>

                            </span>
                        </div>
                        <div class="filter-container d-flex align-items-center mb-3 flex-wrap justify-content-center">
                            <div class="btn-group-toggle d-flex flex-column mr-3 mb-2 text-center" data-toggle="buttons">
                                <label class="btn btn-outline-primary mb-1" style="width: 100%">
                                    <input type="radio" name="filter" id="filter-all" autocomplete="off" value="all"> Todos
                                </label>
                                <label class="btn btn-outline-primary" style="width: 100%">
                                    <input type="radio" name="filter" id="filter-unread" autocomplete="off" value="unread"> Não Lidos
                                </label>
                            </div>
                            <button id="mark-all-read" class="btn btn-icon ml-2 p-0" style="background: none; border: none; margin-left: 0px !important; margin-right: 20px !important;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" id="doublecheck" width="24" height="24">
                                    <path fill="#be1523" fill-rule="evenodd" d="M6.00008 15.5858L15.293 6.29289 16.7072 7.70711 6.70718 17.7071C6.51965 17.8946 6.26529 18 6.00008 18 5.73486 18 5.4805 17.8946 5.29297 17.7071L1.29297 13.7071 2.70718 12.2929 6.00008 15.5858zM12.0003 15.5859L21.2928 6.29291 22.7071 7.70709 12.7075 17.7071C12.3171 18.0976 11.684 18.0976 11.2934 17.7072L9.79297 16.2072 11.207 14.7928 12.0003 15.5859z" clip-rule="evenodd" class="color000000 svgShape"></path>
                                </svg>
                            </button>
                        </div>


                        <!-- Success Modal -->
                        <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                            <div class="modal-body text-center">
                                <h5 class="modal-title mb-3" id="successModalLabel">Sucesso</h5>
                                <p>Todas as conversas foram marcadas como lidas.</p>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                            </div>
                            </div>
                        </div>
                        </div>




                        <div class="conversations-list">
                            @if($lastContactID == false)
                                <div class="d-flex mt-3 mt-md-2 pl-3 pl-md-0 mb-3 pl-md-0" style="padding-left: 0 !important;">
                                    <span>{{__('Click the text bubble to send a new message.')}}</span>
                                </div>
                            @else
                                @include('elements.preloading.messenger-contact-box', ['limit'=>3])
                            @endif
                        </div>

                    </div>
                    <div class="col-9 col-xl-9 col-lg-9 col-md-9 col-sm-9 col-xs-10 border conversation-wrapper rounded-right p-0 d-flex flex-column min-vh-100">
                        @include('elements.message-alert')
                        @include('elements.messenger.messenger-conversation-header')
                        @include('elements.messenger.messenger-new-conversation-header')
                        @include('elements.preloading.messenger-conversation-header-box')
                        @include('elements.preloading.messenger-conversation-box')
                        <div class="conversation-content pt-4 pb-1 px-3 flex-fill">
                        </div>
                        



                        <div class="dropzone-previews dropzone w-100 ppl-0 pr-0 pt-1 pb-1"></div>
                        <div class="msg-price d-none alert  text-center" style="background-color: #be1523">
                            <span class="font-weight-bold" style="color: white">Enviando uma mensagem Paga!</span>
                        </div>
                        <div class="conversation-writeup pt-1 pb-1 mb-1 {{!$lastContactID ? 'hidden' : ''}}">
    <form class="message-form w-100 d-flex flex-column align-items-start">
        <div class="input-group flex-grow-1 w-100">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="receiverID" id="receiverID" value="">
            <textarea name="message" class="form-control messageBoxInput" placeholder="{{__('Write a message..')}}" onkeyup="messenger.textAreaAdjust(this)"></textarea>
        </div>
    </form>
    <div class="messenger-buttons-wrapper d-flex justify-content-between mt-2 w-100">
        <div class="d-flex">

        <button class="btn btn-outline-primary btn-rounded-icon messenger-button attach-file mx-2 file-upload-button to-tooltip" data-placement="top" title="{{__('Attach file')}}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" style="background: none;">
                <g fill="#c22d39" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                    <path d="M4,4c-1.1,0 -2,0.9 -2,2v12c0,1.1 0.9,2 2,2h16c1.1,0 2,-0.9 2,-2v-12c0,-1.1 -0.9,-2 -2,-2zM4,5h16c0.55,0 1,0.45 1,1v10.91l-4.26,-3.83c-0.67,-0.6 -1.69,-0.6 -2.34,0l-3.46,3.12l-1.66,-1.42c-0.66,-0.57 -1.63,-0.57 -2.29,0l-2.99,2.57v-10.05c0,-0.55 0.45,-1 1,-1zM6,8.5c-1.1,0 -2,0.9 -2,2s0.9,2 2,2s2,-0.9 2,-2s-0.9,-2 -2,-2z"></path>
                </g>
            </svg>
        </button>



            <div class="ml-2 d-flex align-items-center justify-content-center">
            <span class="h-pill-primary rounded trigger" data-toggle="tooltip" data-placement="top" title="Like">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="20" height="20">
                    <g fill="#c22d39" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                        <g transform="scale(5.12,5.12)">
                            <path d="M25,2c-0.05176,0.00121 -0.10335,0.00643 -0.1543,0.01563c-12.61863,0.08534 -22.8457,10.34585 -22.8457,22.98438c0,12.69137 10.30863,23 23,23c12.69137,0 23,-10.30863 23,-23c0,-12.6372 -10.22499,-22.89691 -22.8418,-22.98437c-0.05224,-0.0094 -0.10514,-0.01462 -0.1582,-0.01562zM25,4c11.61063,0 21,9.38937 21,21c0,11.61063 -9.38937,21 -21,21c-11.61063,0 -21,-9.38937 -21,-21c0,-11.61063 9.38937,-21 21,-21zM17,18c-1.65685,0 -3,1.34315 -3,3c0,1.65685 1.34315,3 3,3c1.65685,0 3,-1.34315 3,-3c0,-1.65685 -1.34315,-3 -3,-3zM33,18c-1.65685,0 -3,1.34315 -3,3c0,1.65685 1.34315,3 3,3c1.65685,0 3,-1.34315 3,-3c0,-1.65685 -1.34315,-3 -3,-3zM11.95703,28.98828c-0.37136,0.01225 -0.70532,0.22937 -0.86722,0.56381c-0.16189,0.33444 -0.12503,0.73107 0.09573,1.02994c0,0 5.23112,7.41797 13.81445,7.41797c8.58333,0 13.81445,-7.41797 13.81445,-7.41797c0.32145,-0.44981 0.21739,-1.07504 -0.23242,-1.39648c-0.44981,-0.32145 -1.07504,-0.21739 -1.39648,0.23242c0,0 -4.76888,6.58203 -12.18555,6.58203c-7.41667,0 -12.18555,-6.58203 -12.18555,-6.58203c-0.19401,-0.27987 -0.5171,-0.44178 -0.85742,-0.42969z"></path>
                        </g>
                    </g>
                </svg>
            </span>

            </div>
        </div>
        <div class="d-flex">
    @if((GenericHelper::creatorCanEarnMoney(Auth::user()) && !(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))))
        <button class="btn btn-outline-primary btn-rounded-icon messenger-button mx-2 to-tooltip" data-placement="top" title="{{__('Message price')}}" onClick="messenger.showSetPriceDialog()">

        </button>
    @endif

    @if((GenericHelper::creatorCanEarnMoney(Auth::user()) && !(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))) /*|| Auth::user()->role_id === 1*/)
                                    <button class="btn btn-outline-primary btn-rounded-icon messenger-button mx-2 to-tooltip" data-placement="top" title="{{__('Message price')}}" onClick="messenger.showSetPriceDialog()">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <span class="message-price-lock">            
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="20" height="20" style="background: none;">
                                                    <g fill="#c22d39" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                                                        <g transform="scale(5.12,5.12)">
                                                            <path d="M28.625,2c-0.33594,0.00391 -0.68359,0.01563 -1.03125,0.0625c-0.6875,0.08984 -1.4375,0.31641 -2,0.9375c-0.01172,0.01172 -0.01953,0.01953 -0.03125,0.03125l-22.65625,22.65625c-1.10547,1.10547 -1.08984,2.89063 -0.0625,4.0625l0.0625,0.0625l17.375,17.40625c1.10547,1.10547 2.89063,1.05859 4.0625,0.03125l0.03125,-0.03125h0.03125l22.6875,-22.71875c1.13672,-1.13672 1,-2.69922 1,-4v-0.15625c0,0 0.00781,0.00781 0,-0.03125c0,-0.00391 0,-0.02344 0,-0.03125c0,-0.03906 0.00391,-0.12891 0,-0.1875c-0.00391,-0.08594 -0.02734,-0.16016 -0.03125,-0.28125c-0.00781,-0.40234 -0.02344,-0.99219 -0.03125,-1.6875c-0.01562,-1.39062 -0.02344,-3.23828 -0.03125,-5.09375c-0.01172,-3.71094 0,-7.42969 0,-7.53125c0,-1.92187 -1.57812,-3.5 -3.5,-3.5h-14.90625c-0.3125,0 -0.63281,-0.00391 -0.96875,0zM28.625,4c0.29688,0 0.63281,0 0.96875,0h14.90625c0.875,0 1.5,0.625 1.5,1.5c0,0.10156 -0.01172,3.81641 0,7.53125c0.00781,1.85547 0.01563,3.72656 0.03125,5.125c0.00781,0.69922 0.01953,1.26563 0.03125,1.6875c0.00391,0.21094 0.02344,0.40234 0.03125,0.53125c0.00391,0.05078 -0.00391,0.07813 0,0.125c0,0.01172 0,0.01953 0,0.03125c-0.00391,1.27734 -0.14844,2.30469 -0.40625,2.5625l-22.65625,22.65625c-0.01172,0.01172 -0.01953,0.02344 -0.03125,0.03125c-0.42578,0.34375 -0.99219,0.28906 -1.28125,0l-17.375,-17.34375l-0.03125,-0.03125c-0.34375,-0.42578 -0.28906,-1.02344 0,-1.3125l22.75,-22.75c0.13281,-0.15234 0.33203,-0.25391 0.78125,-0.3125c0.22656,-0.03125 0.48438,-0.03125 0.78125,-0.03125zM39,7c-2.19922,0 -4,1.80078 -4,4c0,2.19922 1.80078,4 4,4c2.19922,0 4,-1.80078 4,-4c0,-2.19922 -1.80078,-4 -4,-4zM39,9c1.11719,0 2,0.88281 2,2c0,1.11719 -0.88281,2 -2,2c-1.11719,0 -2,-0.88281 -2,-2c0,-1.11719 0.88281,-2 2,-2zM32.3125,17l-1.625,1.59375c-1.30078,-1 -4.28906,-2.08594 -6.6875,0.3125c-4.5,4.5 4.39844,8.78906 1,12.1875c-1.19922,1.19922 -2.70703,1.69922 -4.90625,-0.5c-2.19922,-2.19922 -1.08594,-4.50781 -0.1875,-5.40625l-1.40625,-1.375c-2.80078,3.30078 -1.40625,6.10156 -0.40625,7.5l-1.40625,1.375l1.40625,1.40625l1.3125,-1.28125c1.30078,0.89844 4.58203,2.58203 7.28125,-0.21875c2.30078,-2.30078 1.11719,-4.88672 -0.28125,-7.1875c-1.19922,-1.89844 -2.11719,-3.60156 -0.71875,-5c0.60156,-0.60156 2.30469,-1.82031 4.40625,0.28125c1.5,1.5 1.30469,3.10547 0.40625,4.40625l1.40625,1.40625c1.10156,-2 1.88672,-4.10156 0.1875,-6.5l1.59375,-1.59375z"></path>
                                                    </g>
                                                </svg>
                                            </span>

                                            <span class="message-price-close d-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="20" height="20" style="background: none;">
                                                    <g fill="#c22d39" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                                                        <g transform="scale(5.12,5.12)">
                                                            <path d="M28.625,2c-0.33594,0.00391 -0.68359,0.01563 -1.03125,0.0625c-0.6875,0.08984 -1.4375,0.31641 -2,0.9375c-0.01172,0.01172 -0.01953,0.01953 -0.03125,0.03125l-22.65625,22.65625c-1.10547,1.10547 -1.08984,2.89063 -0.0625,4.0625l0.0625,0.0625l17.375,17.40625c1.10547,1.10547 2.89063,1.05859 4.0625,0.03125l0.03125,-0.03125h0.03125l22.6875,-22.71875c1.13672,-1.13672 1,-2.69922 1,-4v-0.15625c0,0 0.00781,0.00781 0,-0.03125c0,-0.00391 0,-0.02344 0,-0.03125c0,-0.03906 0.00391,-0.12891 0,-0.1875c-0.00391,-0.08594 -0.02734,-0.16016 -0.03125,-0.28125c-0.00781,-0.40234 -0.02344,-0.99219 -0.03125,-1.6875c-0.01562,-1.39062 -0.02344,-3.23828 -0.03125,-5.09375c-0.01172,-3.71094 0,-7.42969 0,-7.53125c0,-1.92187 -1.57812,-3.5 -3.5,-3.5h-14.90625c-0.3125,0 -0.63281,-0.00391 -0.96875,0zM28.625,4c0.29688,0 0.63281,0 0.96875,0h14.90625c0.875,0 1.5,0.625 1.5,1.5c0,0.10156 -0.01172,3.81641 0,7.53125c0.00781,1.85547 0.01563,3.72656 0.03125,5.125c0.00781,0.69922 0.01953,1.26563 0.03125,1.6875c0.00391,0.21094 0.02344,0.40234 0.03125,0.53125c0.00391,0.05078 -0.00391,0.07813 0,0.125c0,0.01172 0,0.01953 0,0.03125c-0.00391,1.27734 -0.14844,2.30469 -0.40625,2.5625l-22.65625,22.65625c-0.01172,0.01172 -0.01953,0.02344 -0.03125,0.03125c-0.42578,0.34375 -0.99219,0.28906 -1.28125,0l-17.375,-17.34375l-0.03125,-0.03125c-0.34375,-0.42578 -0.28906,-1.02344 0,-1.3125l22.75,-22.75c0.13281,-0.15234 0.33203,-0.25391 0.78125,-0.3125c0.22656,-0.03125 0.48438,-0.03125 0.78125,-0.03125zM39,7c-2.19922,0 -4,1.80078 -4,4c0,2.19922 1.80078,4 4,4c2.19922,0 4,-1.80078 4,-4c0,-2.19922 -1.80078,-4 -4,-4zM39,9c1.11719,0 2,0.88281 2,2c0,1.11719 -0.88281,2 -2,2c-1.11719,0 -2,-0.88281 -2,-2c0,-1.11719 0.88281,-2 2,-2zM32.3125,17l-1.625,1.59375c-1.30078,-1 -4.28906,-2.08594 -6.6875,0.3125c-4.5,4.5 4.39844,8.78906 1,12.1875c-1.19922,1.19922 -2.70703,1.69922 -4.90625,-0.5c-2.19922,-2.19922 -1.08594,-4.50781 -0.1875,-5.40625l-1.40625,-1.375c-2.80078,3.30078 -1.40625,6.10156 -0.40625,7.5l-1.40625,1.375l1.40625,1.40625l1.3125,-1.28125c1.30078,0.89844 4.58203,2.58203 7.28125,-0.21875c2.30078,-2.30078 1.11719,-4.88672 -0.28125,-7.1875c-1.19922,-1.89844 -2.11719,-3.60156 -0.71875,-5c0.60156,-0.60156 2.30469,-1.82031 4.40625,0.28125c1.5,1.5 1.30469,3.10547 0.40625,4.40625l1.40625,1.40625c1.10156,-2 1.88672,-4.10156 0.1875,-6.5l1.59375,-1.59375z"></path>
                                                    </g>
                                                </svg>

                                            </span>
                                        </div>
                                    </button>
    @endif


    <button class="btn btn-outline-primary btn-rounded-icon messenger-button send-message mr-2 to-tooltip" onClick="messenger.sendMessage()" data-placement="top" title="{{__('Send message')}}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="20" height="20" style="background: none;">
            <g fill="#c22d39" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                <g transform="scale(5.12,5.12)">
                    <path d="M44.875,4c-0.09766,0.01563 -0.19141,0.04688 -0.28125,0.09375l-40,17.1875c-0.35547,0.15625 -0.58594,0.50781 -0.59375,0.89453c-0.00781,0.39063 0.21484,0.74609 0.5625,0.91797l14.90625,7.4375l7.4375,14.90625c0.17188,0.34766 0.52734,0.57031 0.91797,0.5625c0.38672,-0.00781 0.73828,-0.23828 0.89453,-0.59375l17.1875,-40c0.14844,-0.32812 0.10938,-0.71484 -0.10547,-1.00391c-0.21094,-0.29297 -0.56641,-0.44531 -0.92578,-0.40234zM40.625,7.96875l-20.625,20.625l-12.625,-6.3125zM42.03125,9.375l-14.3125,33.25l-6.3125,-12.625z"></path>
                </g>
            </g>
        </svg>
    </button>
</div>

    </div>
</div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('elements.standard-dialog',[
    'dialogName' => 'message-delete-dialog',
    'title' => __('Delete message'),
    'content' => __('Are you sure you want to delete this message?'),
    'actionLabel' => __('Delete'),
    'actionFunction' => 'messenger.deleteMessage();',
])
@stop

<style>
.blur-effect {
    filter: blur(8px);
}


.filter-container {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.filter-container .btn-group-toggle {
    margin-right: 1rem;
    margin-bottom: 0.5rem;
}


.btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .filter-container {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-container .btn-group-toggle {
        margin-right: 0;
        margin-bottom: 0.5rem;
        width: 100%;
    }

    .filter-container .btn-group-toggle .btn {
        width: 100%;
    }

    .filter-container .d-flex {
        width: 100%;
        justify-content: space-between;
    }
}

.filter-container {
    display: flex;
    justify-content: center; /* Centraliza o conteúdo do contêiner horizontalmente */
}

.btn-group-toggle {
    display: flex;
    flex-direction: column;
    align-items: center; /* Centraliza os botões verticalmente */
}

.btn-group-toggle label {
    text-align: center; /* Centraliza o texto dentro dos labels */
    display: flex;
    justify-content: center; /* Centraliza o conteúdo do label */
}

.btn-group-toggle input[type="radio"] {
    display: none; /* Oculta o botão de rádio, se necessário */
}

.msg-price{
    padding: 0 !important;
    margin-bottom: 2px !important ;
}

</style>

