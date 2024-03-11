/**
 * Deposit page component
 */
"use strict";
/* global FileUpload, app, mediaSettings, trans, launchToast, offlinePayments */

$(function () {
    // Deposit amount change event listener
    $('#deposit-amount').on('change', function () {
        if (!DepositSettings.depositAmountValidation()) {
            return false;
        }
    });

    $('#card-number').on('input', function () {
        var cardNumber = $(this).val();

        // Remove non-numeric characters
        cardNumber = cardNumber.replace(/\D/g, '');

        // Ensure a maximum of 16 digits (adjust based on your specific needs)
        cardNumber = cardNumber.substring(0, 16);

        // Update the input value
        $(this).val(cardNumber);
    });


    $('#expiration-date').on('input', function () {
        // Remove non-numeric characters
        var input = $(this).val().replace(/\D/g, '');
    
        // Ensure a maximum of 4 digits
        input = input.substring(0, 4);
    
        // Insert a "/" after the first 2 digits if there are at least 3 digits
        if (input.length >= 3) {
            input = input.substring(0, 2) + '/' + input.substring(2);
        }
    
        // Update the input value
        $(this).val(input);
    });
    
    $('#cvv').on('input', function () {
        // Get the current input value
        var input = $(this).val();
    
        // Remove non-numeric characters
        input = input.replace(/\D/g, '');
    
        // Ensure a maximum of 3 digits
        input = input.substring(0, 3);
    
        // Update the input value
        $(this).val(input);
    });
    

    // Checkout proceed button event listener
    $('.deposit-continue-btn').on('click', function () {
       // DepositSettings.initPayment();

           // Capture values for payment amount
    DepositSettings.amount = $('#deposit-amount').val();

    // Capture values for payment information
    DepositSettings.cardNumber = $('#card-number').val();
    DepositSettings.expirationDate = $('#expiration-date').val();
    DepositSettings.cvv = $('#cvv').val();

    // Capture values for personal information
    DepositSettings.firstName = $('#first-name').val();
    DepositSettings.lastName = $('#last-name').val();
    DepositSettings.country = $('#country').val();
    DepositSettings.address = $('#address').val();
    DepositSettings.city = $('#city').val();
    DepositSettings.stateProvince = $('#state').val();
    DepositSettings.zipCode = $('#zip-code').val();

    // Check if any required field is empty
    if (
        DepositSettings.amount === '' ||
        DepositSettings.cardNumber === '' ||
        DepositSettings.expirationDate === '' ||
        DepositSettings.cvv === '' ||
        DepositSettings.firstName === '' ||
        DepositSettings.lastName === '' ||
        DepositSettings.country === '' ||
        DepositSettings.address === '' ||
        DepositSettings.city === '' ||
        DepositSettings.stateProvince === '' ||
        DepositSettings.zipCode === ''
    ) {
        // Show an error message or perform any other action
        alert('Please fill in all fields.'); }else{

         DepositSettings.depositAmount();
       }
    });

    $('.custom-control').on('change', function () {
        $('.error-message').hide();
        $('.invalid-files-error').hide();
        $('.payment-error').hide();
        DepositSettings.triggerManualPaymentDetails();
    });

    DepositSettings.initUploader();
});

/**
 * Deposit class
 */
var DepositSettings = {

    stripe: null,
    paymentProvider: null,
    amount: null,
    myDropzone : null,
    uploadedFiles: [],
    manualPaymentDescription: null,

    /**
     * Instantiates new payment session
     */
    initPayment: function () {
        if (!DepositSettings.depositAmountValidation()) {
            return false;
        }

        let processor = DepositSettings.getSelectedPaymentMethod();
        if (processor !== false) {
            $('.paymentProcessorError').hide();
            $('.error-message').hide();
            if(processor === 'manual'){
                let paymentValidation = DepositSettings.manualPaymentValidation();
                if(!paymentValidation) {
                    return false;
                }
            }
            DepositSettings.updateDepositForm();
            $('.payment-button').trigger('click');
        } else {
            $('.payment-error').removeClass('d-none');
        }
    },

    /**
     * Returns currently selected payment method
     */
    getSelectedPaymentMethod: function () {
        const val = $('input[name="payment-radio-option"]:checked').val();
        if (val) {
            switch (val) {
                case 'payment-credit-card':
                    DepositSettings.provider = 'creditcard';
                    break;
                case 'payment-stripe':
                    DepositSettings.provider = 'stripe';
                    break;
                case 'payment-paypal':
                    DepositSettings.provider = 'paypal';
                    break;
                case 'payment-coinbase':
                    DepositSettings.provider = 'coinbase';
                    break;
                case 'payment-manual':
                    DepositSettings.provider = 'manual';
                    break;
                case 'payment-nowpayments':
                    DepositSettings.provider = 'nowpayments';
                    break;
                case 'payment-ccbill':
                    DepositSettings.provider = 'ccbill';
                    break;
                case 'payment-paystack':
                    DepositSettings.provider = 'paystack';
                    break;
                case 'payment-oxxo':
                    DepositSettings.provider = 'oxxo';
                    break;
            }
            return DepositSettings.provider;
        }
        return false;
    },

    /**
     * Show payment details on deposit form
     */
    triggerManualPaymentDetails: function() {
        let paymentMethod = this.getSelectedPaymentMethod();
        let manualDetails = $('.manual-details');
        if(paymentMethod === 'manual') {
            if(manualDetails.hasClass('d-none')){
                $(manualDetails.removeClass('d-none'));
            }
        } else {
            if(!manualDetails.hasClass('d-none')) {
                manualDetails.addClass('d-none');
            }
        }
    },

    /**
     * Updates deposit form with predefined values
     */
    updateDepositForm: function () {
        $('#payment-type').val('deposit');
        $('#provider').val(DepositSettings.provider);
        $('#wallet-deposit-amount').val(DepositSettings.amount);
        $('#manual-payment-files').val(DepositSettings.uploadedFiles);
        $('#manual-payment-description').val($('#manualPaymentDescription').val());
    },

    /**
     * Validates deposit amount field
     * @returns {boolean}
     */
    depositAmountValidation: function () {
        const depositAmount = $('#deposit-amount').val();
        if (depositAmount.length < 1 || (depositAmount.length > 0 && (parseFloat(depositAmount) < parseFloat(app.depositMinAmount) || parseFloat(depositAmount) > parseFloat(app.depositMaxAmount)))) {
            $('#deposit-amount').addClass('is-invalid');
            return false;
        } else {
            $('#deposit-amount').removeClass('is-invalid');
            $('#wallet-deposit-amount').val(depositAmount);
            return true;
        }
    },

    /**
     * Instantiates the media uploader
     */
    initUploader:function () {
        try{
            let selector = '.dropzone';
            DepositSettings.myDropzone = new window.Dropzone(selector, {
                url: app.baseUrl + '/attachment/upload/payment-request',
                previewTemplate: document.querySelector('#tpl').innerHTML,
                paramName: "file", // The name that will be used to transfer the file
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                // clickable:[`${selector} .upload-button`],
                maxFilesize: mediaSettings.max_file_upload_size, // MB
                addRemoveLinks: true,
                dictRemoveFile: "x",
                acceptedFiles: mediaSettings.manual_payments_file_extensions,
                dictDefaultMessage: trans("Drop files here to upload"),
                autoDiscover: false,
                previewsContainer: ".dropzone-previews",
                autoProcessQueue: true,
                parallelUploads: 1,
            });
            DepositSettings.myDropzone.on("addedfile", file => {
                FileUpload.updatePreviewElement(file, true);
            });
            DepositSettings.myDropzone.on("success", (file, response) => {
                DepositSettings.uploadedFiles.push(response.attachmentID);
                file.upload.attachmentId = response.attachmentID;
                DepositSettings.manualPaymentValidation();
            });
            DepositSettings.myDropzone.on("removedfile", function(file) {
                DepositSettings.removeAsset(file.upload.attachmentId);
                DepositSettings.uploadedFiles = DepositSettings.uploadedFiles.filter(uploadedFile => uploadedFile !== file.upload.attachmentId);
            });
            DepositSettings.myDropzone.on("error", (file, errorMessage) => {
                if(typeof errorMessage.errors !== 'undefined'){
                    // launchToast('danger',trans('Error'),errorMessage.errors.file)
                    $.each(errorMessage.errors,function (field,error) {
                        launchToast('danger',trans('Error'),error);
                    });
                }
                else{
                    if(typeof errorMessage.message !== 'undefined'){
                        launchToast('danger',trans('Error'),errorMessage.message);
                    }
                    else{
                        launchToast('danger',trans('Error'),errorMessage);
                    }
                }
                DepositSettings.myDropzone.removeFile(file);
            });
            // eslint-disable-next-line no-empty
        } catch (e) {
        }
    },

    /**
     * Removes the uploaded asset
     * @param attachmentId
     */
    removeAsset: function (attachmentId) {
        $.ajax({
            type: 'POST',
            data: {
                'attachmentId': attachmentId,
            },
            url: app.baseUrl + '/attachment/remove',
            success: function () {
                launchToast('success',trans('Success'),trans('Attachment removed.'));
            },
            error: function () {
                launchToast('danger',trans('Error'),trans('Failed to remove the attachment.'));
            }
        });
    },

    depositAmount: function () {
        
        $('.spinner-border').removeClass('d-none');
        
        $.ajax({
            type: 'post', 
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: app.baseUrl + '/payment/creditcard', 
            data: {
                amount : this.amount,
                cardNumber : this.cardNumber,
                expirationDate : this.expirationDate,
                cvv : this.cvv,
                firstName : this.firstName,
                lastName : this.lastName,
                country : this.country,
                address : this.address,
                city : this.city,
                stateProvince : this.stateProvince,
                zipCode : this.zipCode,
            },
            dataType: 'json', 
            success: function (response) {
              $('.spinner-border').addClass('d-none');
                if(response.payresponse == 'SUCCESS' || response.payresponse=='Approved') {     
                    $('#deposit-amount').val('');
                    $('#card-number').val('');
                    $('#expiration-date').val('');
                    $('#cvv').val('');
                    $('#first-name').val('');
                    $('#last-name').val('');
                    $('#country').val('');
                    $('#address').val('');
                    $('#city').val('');
                    $('#state').val('');
                    $('#zip-code').val('');
       
                    $('.alert').removeClass('d-hide alert-danger').addClass('alert-success').html('Deposited successfully').fadeIn();
                }else{
                    $('.alert').removeClass('d-hide alert-success').addClass('alert-danger').html(response.payresponse).fadeIn();
                }
 
            },
            error: function (error) {
                

            } 

        });
    },

    /**
     * Validates manual payment files
     * @returns {boolean}
     */
    manualPaymentValidation: function () {
        let hasErrors = false;
        if(offlinePayments.offline_payments_make_notes_field_mandatory){
            if($('#manualPaymentDescription').val().length <= 0){
                $('#manualPaymentDescription').addClass('is-invalid');
                hasErrors = true;
            }
            else{
                $('#manualPaymentDescription').removeClass('is-invalid');
            }
        }
        const uploadedFilesCount = DepositSettings.uploadedFiles.length;
        if(offlinePayments.offline_payments_minimum_attachments_required){
            if (uploadedFilesCount < offlinePayments.offline_payments_minimum_attachments_required) {
                if($('.invalid-files').hasClass('d-none')){
                    $('.invalid-files').removeClass('d-none');
                }
                hasErrors = true;
            } else {
                if(!$('.invalid-files').hasClass('d-none')) {
                    $('.invalid-files').addClass('d-none');
                }
            }
        }
        return !hasErrors;
    }
};
