/**
 * Post create (helper) component
 */
"use strict";
/* global app, Post, user, FileUpload, updateButtonState, launchToast, trans, redirect, trans_choice, mediaSettings, passesMinMaxPPVContentCreationLimits, getWebsiteFormattedAmount */

$(function () {
    $("#post-price").keypress(function(e) {
        if(e.which === 13) {
            PostCreate.savePostPrice();
        }
    });
});

var PostCreate = {
    // Paid post price
    postPrice : 0,
    isSavingRedirect: false,
    postNotifications: false,
    isBulkMode: false,
    postReleaseDate: null,
    postExpireDate: null,
    attachmentSchedules: {},

    /**
     * Toggles post notification state
     */
    togglePostNotifications: function(){
        let buttonIcon = '';
        if(PostCreate.postNotifications === true){
            PostCreate.postNotifications = false;
            buttonIcon = `<div class="d-flex justify-content-center align-items-center mr-1"><ion-icon class="icon-medium" name="notifications-off-outline"></ion-icon></div>`;
        }
        else{
            buttonIcon = `<div class="d-flex justify-content-center align-items-center mr-1"><ion-icon class="icon-medium" name="notifications-outline"></ion-icon></div>`;
            PostCreate.postNotifications = true;
        }
        $('.post-notification-icon').html(buttonIcon);
    },

    /**
     * Shows up the post price setter dialog
     */
    showSetPricePostDialog: function(){
        $('#post-set-price-dialog').modal('show');
    },

    /**
     * Saves the post price into the state
     */
    savePostPrice: function(){
        PostCreate.postPrice = $('#post-price').val();
        let hasError = false;
        if(!passesMinMaxPPVContentCreationLimits(PostCreate.postPrice)){
            hasError = 'min';
        }
        if(PostCreate.postExpireDate !== null){
            hasError = 'ppv';
        }
        if(hasError){
            $('.post-price-error').addClass('d-none');
            $('#post-set-price-dialog .'+hasError+'-error').removeClass('d-none');
            $('#post-price').addClass('is-invalid');
            return false;
        }
        $('.post-price-label').html('('+getWebsiteFormattedAmount(PostCreate.postPrice)+')');
        $('#post-set-price-dialog').modal('hide');
        $('#post-price').removeClass('is-invalid');
    },
    /**
     * Clears up post price
     */
    clearPostPrice: function(){
        PostCreate.postPrice = 0;
        $('#post-price').val(0);
        $('.post-price-label').html('');
        $('#post-set-price-dialog').modal('hide');
        $('#post-price').removeClass('is-invalid');
    },

    /**
     * Initiates the post draft data, if available
     * @param data
     * @param type
     */
    initPostDraft: function(data, type = 'draft'){
        Post.initialDraftData = Post.draftData;
        if(data){
            Post.draftData = data;
            if(type === 'draft'){
                FileUpload.attachaments = data.attachments;
            }
            else{
                data.attachments.map(function (item) {
                    FileUpload.attachaments.push({attachmentID: item.id, path: item.path, type:item.attachmentType, thumbnail:item.thumbnail});
                });
            }
            $('#dropzone-uploader').val(Post.draftData.text);
        }
    },

    /**
     * Clears up post draft data
     */
    clearDraft: function(){
        // Clearing attachments from the backend
        Post.draftData.attachments.map(function (value) {
            FileUpload.removeAttachment(value.attachmentID);
        });
        // Removing previews
        $('.dropzone-previews .dz-preview ').each(function (index, item) {
            $(item).remove();
        });
        // Clearing Fileupload class attachments
        FileUpload.attachaments = [];
        PostCreate.attachmentSchedules = {};
        PostCreate.renderBulkScheduleTable();
        // Clearing up the local storage object
        PostCreate.clearDraftData();
        // Clearing up the text area value
    },

    /**
     * Saves post draft data
     */
    saveDraftData: function(){
        Post.draftData.attachments = FileUpload.attachaments;
        Post.draftData.text = $('#dropzone-uploader').val();
        localStorage.setItem('draftData', JSON.stringify(Post.draftData));
    },

    /**
     * Clears up draft data
     * @param callback
     */
    clearDraftData: function(callback = null){
        localStorage.removeItem('draftData');
        Post.draftData = Post.initialDraftData;
        if(callback !== null){
            callback;
        }
        $('#dropzone-uploader').val(Post.draftData.text);
    },


    /**
     * Populates create/edit post form with draft data
     * @returns {boolean|any}
     */
    populateDraftData: function(){
        const draftData = localStorage.getItem('draftData');
        if(draftData){
            return JSON.parse(draftData);
        }
        else{
            return false;
        }
    },

    /**
     * Save new / update post
     * @param type
     * @param postID
     */
    save: function (type = 'create', postID = false, forceSave = false) {
        if(FileUpload.isLoading === true && forceSave === false){
            $('.confirm-post-save').unbind('click');
            $('.confirm-post-save').on('click',function () {
                PostCreate.save(type, postID, true);
            });
            $('#confirm-post-save').modal('show');
            return false;
        }
        updateButtonState('loading',$('.post-create-button'));
        if(!PostCreate.isBulkMode){
            PostCreate.savePostScheduleSettings();
        }
        else if(!PostCreate.validateBulkSchedules()){
            updateButtonState('loaded',$('.post-create-button'), trans('Save'));
            return false;
        }
        let route = app.baseUrl + '/posts/save';
        let data = {
            'attachments': FileUpload.attachaments,
            'text': $('#dropzone-uploader').val(),
            'price': PostCreate.postPrice,
            'postNotifications' : PostCreate.postNotifications,
            'postReleaseDate': PostCreate.postReleaseDate,
            'postExpireDate': PostCreate.postExpireDate
        };
        if(type === 'create'){
            data.type = 'create';
            data.bulkMode = PostCreate.isBulkMode;
            if(PostCreate.isBulkMode){
                data.attachmentSchedules = PostCreate.attachmentSchedules;
            }
        }
        else{
            data.type = 'update';
            data.id = postID;
        }
        $.ajax({
            type: 'POST',
            data: data,
            url: route,
            success: function () {
                if(type === 'create'){
                    PostCreate.isSavingRedirect = true;
                    PostCreate.attachmentSchedules = {};
                    PostCreate.clearDraftData(redirect(app.baseUrl+'/'+user.username));
                }
                else{
                    redirect(app.baseUrl+'/posts/'+postID+'/'+user.username);
                }
                updateButtonState('loaded',$('.post-create-button'), trans('Save'));
                $('#confirm-post-save').modal('hide');
            },
            error: function (result) {
                if(result.status === 422 || result.status === 500) {
                    $.each(result.responseJSON.errors, function (field, error) {
                        if (field === 'text') {
                            $('.post-invalid-feedback').html(trans_choice('Your post must contain more than 10 characters.',mediaSettings.max_post_description_size, {'num':mediaSettings.max_post_description_size}));
                            $('#dropzone-uploader').addClass('is-invalid');
                            $('#dropzone-uploader').focus();
                        }
                        if (field === 'attachments') {
                            $('.post-invalid-feedback').html(trans('Your post must contain at least one attachment.'));
                            $('#dropzone-uploader').addClass('is-invalid');
                            $('#dropzone-uploader').focus();
                        }
                        if (field === 'price') {
                            $('.post-invalid-feedback').html(result.responseJSON.message);
                            $('#dropzone-uploader').addClass('is-invalid');
                            $('#dropzone-uploader').focus();
                        }

                        if(field === 'permissions'){
                            launchToast('danger',trans('Error'),error);
                        }
                    });
                }
                else if(result.status === 403){
                    launchToast('danger',trans('Error'),'Post not found.');
                }
                $('#confirm-post-save').modal('hide');
                updateButtonState('loaded',$('.post-create-button'), trans('Save'));
            }
        });
    },

    /**
     * Shows up the post scheduling setting setter dialog
     */
    showPostScheduleDialog: function(){
        $('#post-set-schedule-dialog').modal('show');
    },

    /**
     * Saves the post post scheduling setting into the state
     */
    savePostScheduleSettings: function(){

        if(PostCreate.postPrice !== 0 && $('#post_expire_date').val().length > 0){
            $('#post_expire_date').addClass('is-invalid');
            return false;
        }

        PostCreate.postReleaseDate = $('#post_release_date').val().length ? $('#post_release_date').val() : null;
        PostCreate.postExpireDate = $('#post_expire_date').val().length ? $('#post_expire_date').val() : null;
        $('#post-set-schedule-dialog').modal('hide');
        $('#post_expire_date').removeClass('is-invalid');

    },
    /**
     * Clears up post scheduling setting
     */
    clearPostScheduleSettings: function(){
        PostCreate.postReleaseDate = null;
        PostCreate.postExpireDate = null;
        $('#post_release_date').val('');
        $('#post_expire_date').val('');
        $('#post_expire_date').removeClass('is-invalid');
    },

    /**
     * Initializes bulk posting mode UI
     */
    initBulkPostMode: function () {
        if($('#bulk-post-toggle').length === 0){
            return;
        }

        $('#bulk-post-toggle').on('change', function () {
            PostCreate.isBulkMode = $(this).is(':checked');
            PostCreate.updateBulkModeUI();
        });

        $('#bulk-schedule-table-body').on('change', '.bulk-release-date', function () {
            const attachmentID = $(this).data('attachment-id');
            PostCreate.ensureScheduleItem(attachmentID);
            PostCreate.attachmentSchedules[attachmentID].release_date = $(this).val();
        });

        $('#bulk-schedule-table-body').on('change', '.bulk-expire-date', function () {
            const attachmentID = $(this).data('attachment-id');
            PostCreate.ensureScheduleItem(attachmentID);
            PostCreate.attachmentSchedules[attachmentID].expire_date = $(this).val();
        });
    },

    /**
     * Register listeners after dropzone initialization
     */
    registerDropzoneBulkEvents: function () {
        if(!FileUpload.myDropzone){
            return;
        }

        FileUpload.myDropzone.on("success", function () {
            PostCreate.syncAttachmentSchedules();
        });

        FileUpload.myDropzone.on("removedfile", function () {
            PostCreate.syncAttachmentSchedules();
        });
    },

    /**
     * Updates bulk mode specific UI states
     */
    updateBulkModeUI: function () {
        if(PostCreate.isBulkMode){
            $('#bulk-schedule-container').removeClass('d-none');
            $('#post-scheduling-action').addClass('disabled').attr('title', trans('Scheduling is configured per media in bulk mode.'));
            PostCreate.clearPostScheduleSettings();
            PostCreate.syncAttachmentSchedules();
        }
        else{
            $('#bulk-schedule-container').addClass('d-none');
            $('#post-scheduling-action').removeClass('disabled').attr('title', trans('Schedule your post release or deletion date.'));
        }
    },

    /**
     * Keeps schedules in sync with current uploaded attachments
     */
    syncAttachmentSchedules: function () {
        if(!PostCreate.isBulkMode){
            return;
        }

        const validIDs = [];
        FileUpload.attachaments.forEach(function (attachment) {
            const attachmentID = PostCreate.getAttachmentID(attachment);
            if(!attachmentID){
                return;
            }
            validIDs.push(String(attachmentID));
            PostCreate.ensureScheduleItem(attachmentID);
        });

        Object.keys(PostCreate.attachmentSchedules).forEach(function (attachmentID) {
            if(validIDs.indexOf(String(attachmentID)) < 0){
                delete PostCreate.attachmentSchedules[attachmentID];
            }
        });

        PostCreate.renderBulkScheduleTable();
    },

    /**
     * Adds schedule object for an attachment if missing
     * @param attachmentID
     */
    ensureScheduleItem: function (attachmentID) {
        if(!attachmentID){
            return;
        }
        if(typeof PostCreate.attachmentSchedules[attachmentID] === 'undefined'){
            PostCreate.attachmentSchedules[attachmentID] = {
                release_date: '',
                expire_date: ''
            };
        }
    },

    /**
     * Renders bulk schedule table
     */
    renderBulkScheduleTable: function () {
        const tableBody = $('#bulk-schedule-table-body');
        if(tableBody.length === 0){
            return;
        }

        tableBody.empty();

        FileUpload.attachaments.forEach(function (attachment, index) {
            const attachmentID = PostCreate.getAttachmentID(attachment);
            if(!attachmentID){
                return;
            }
            PostCreate.ensureScheduleItem(attachmentID);
            const schedule = PostCreate.attachmentSchedules[attachmentID];
            const thumbnail = attachment.thumbnail ? `<img src="${attachment.thumbnail}" class="rounded mr-2" width="42" height="42" alt="media-thumbnail" />` : '';

            tableBody.append(`
                <tr>
                    <td class="align-middle">
                        <div class="d-flex align-items-center">
                            ${thumbnail}
                            <span>#${index + 1}</span>
                        </div>
                    </td>
                    <td>
                        <input type="datetime-local" class="form-control bulk-release-date" data-attachment-id="${attachmentID}" value="${schedule.release_date || ''}">
                    </td>
                    <td>
                        <input type="datetime-local" class="form-control bulk-expire-date" data-attachment-id="${attachmentID}" value="${schedule.expire_date || ''}">
                    </td>
                </tr>
            `);
        });
    },

    /**
     * Validates bulk schedule constraints before submit
     * @returns {boolean}
     */
    validateBulkSchedules: function () {
        if(PostCreate.postPrice === 0){
            return true;
        }
        let hasError = false;
        Object.keys(PostCreate.attachmentSchedules).forEach(function (attachmentID) {
            const schedule = PostCreate.attachmentSchedules[attachmentID];
            if(schedule.expire_date && schedule.expire_date.length > 0){
                hasError = true;
            }
        });

        if(hasError){
            launchToast('danger',trans('Error'), trans('Paid posts can not have expiration date.'));
            return false;
        }

        return true;
    },

    /**
     * Gets attachment identifier from upload payload
     * @param attachment
     * @returns {*|boolean}
     */
    getAttachmentID: function (attachment) {
        if(typeof attachment === 'undefined' || attachment === null){
            return false;
        }
        if(typeof attachment.attachmentID !== 'undefined'){
            return attachment.attachmentID;
        }
        if(typeof attachment.id !== 'undefined'){
            return attachment.id;
        }
        return false;
    },

};
