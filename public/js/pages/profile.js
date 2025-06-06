/*
* Feed page & component
*/
"use strict";
/* global app, paginatorConfig, initialPostIDs, PostsPaginator, Post, getCookie, QRCode, StreamsPaginator, postsFilter */
function redirectToCheckout() {
    const currentUrl = window.location.href;
    const baseUrl = currentUrl.endsWith('/') ? currentUrl.slice(0, -1) : currentUrl;
    window.location.href = `${baseUrl}/checkout`;
};

$(function () {
    if(typeof paginatorConfig !== 'undefined'){
        if((paginatorConfig.total > 0 && paginatorConfig.total > paginatorConfig.per_page) && paginatorConfig.hasMore) {
            PostsPaginator.initScrollLoad();
        }
        PostsPaginator.init(paginatorConfig.next_page_url, '.posts-wrapper');
    }
    else{
        // eslint-disable-next-line no-console
        console.error('Pagination failed to initialize.');
    }

    PostsPaginator.initPostsGalleries(initialPostIDs);
    Post.setActivePage('profile');
    if(getCookie('app_prev_post') !== null){
        PostsPaginator.scrollToLastPost(getCookie('app_prev_post'));
    }
    Post.initPostsMediaModule();
    Post.initGalleryModule('.recent-media');
    if(app.feedDisableRightClickOnMedia !== null){
        Post.disablePostsRightClick();
    }

    if(postsFilter === 'streams') {
        if(typeof paginatorConfig !== 'undefined'){
            if((paginatorConfig.total > 0 && paginatorConfig.total > paginatorConfig.per_page) && paginatorConfig.hasMore) {
                StreamsPaginator.initScrollLoad();
            }
            StreamsPaginator.init(paginatorConfig.next_page_url, '.streams-wrapper');
        }
        else{
            // eslint-disable-next-line no-console
            console.error('Pagination failed to initialize.');
        }
    }

});

$(window).scroll(function(){
    var top = $(window).scrollTop();
    if ($(".main-wrapper").offset().top < top) {
        $(".profile-widgets-area").addClass("sticky-profile-widgets");
    } else {
        $(".profile-widgets-area").removeClass("sticky-profile-widgets");
    }
});

window.onunload = function(){
    // Reset scrolling to top
    $(".inline-border-tabs").get(0).scrollIntoView();

};

// eslint-disable-next-line no-unused-vars
var Profile = {

    /**
     * Toggles profile's description
     */
    toggleFullDescription:function () {
        $('.profile-description-holder .label-less, .profile-description-holder .label-more').addClass('d-none');
        if($('.description-content').hasClass('line-clamp-1')){
            $('.description-content').removeClass('line-clamp-1');
            $('.profile-description-holder .label-less').removeClass('d-none');
        }
        else{
            $('.description-content').addClass('line-clamp-1');
            $('.profile-description-holder .label-more').removeClass('d-none');
        }
    },

    /**
     * Toggles profile's bundles section, if available
     */
    toggleBundles:function () {
        $('.subscription-holder .label-less, .subscription-holder .label-more').addClass('d-none');
        if($('.subscription-bundles').hasClass('d-none')){
            $('.subscription-bundles').removeClass('d-none');
            $('.subscription-holder .label-less').removeClass('d-none');
            $('.subscription-holder .label-icon').html('<ion-icon name="chevron-up-outline"></ion-icon>');
        }
        else{
            $('.subscription-bundles').addClass('d-none');
            $('.subscription-holder .label-more').removeClass('d-none');
            $('.subscription-holder .label-icon').html('<ion-icon name="chevron-down-outline"></ion-icon>');
        }
    },

    /**
     * Generates QR code image for the given profile
     * @param options
     */
    getProfileQRCode: function () {
        var QRoptions = {
            text: window.location.href,
        };
        $('#qrcode').html('');
        new QRCode(document.getElementById("qrcode"), QRoptions);
        $('#qr-code-dialog').modal('show');
    },

    /**
     * Saves QR Canvas to img
     */
    downloadQRCode: function () {
        var canvas = $("#qrcode canvas")[0];
        var image = canvas.toDataURL();
        var aDownloadLink = document.createElement('a');
        aDownloadLink.download = 'canvas_image.png';
        aDownloadLink.href = image;
        aDownloadLink.click();
    },

    redirectToCheckout: function() {
        const currentUrl = window.location.href;
        const baseUrl = currentUrl.endsWith('/') ? currentUrl.slice(0, -1) : currentUrl;
        window.location.href = `${baseUrl}/checkout`;
    }

};
