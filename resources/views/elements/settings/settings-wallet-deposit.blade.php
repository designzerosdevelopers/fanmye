<h5 class="mt-3">{{__('Proceed with payment')}}</h5>
<div class="input-group mb-3 mt-3">
    <div class="input-group-prepend">
        <span class="input-group-text" id="amount-label">@include('elements.icon',['icon'=>'cash-outline','variant'=>'medium'])</span>
    </div>
    <input class="form-control" placeholder="{{\App\Providers\PaymentsServiceProvider::getDepositLimitAmounts()}}"
           aria-label="{{__('Username')}}"
           aria-describedby="amount-label"
           id="deposit-amount"
           type="number"
           min="{{\App\Providers\PaymentsServiceProvider::getDepositMinimumAmount()}}"
           step="1"
           max="{{\App\Providers\PaymentsServiceProvider::getDepositMaximumAmount()}}">
    <div class="invalid-feedback">{{__('Please enter a valid amount.')}}</div>
</div>



<div>
    <div class="payment-method">
 {{-- @if(config('creditcard.nmi_key'))
        <div class="custom-control custom-radio mb-1">
            <input type="radio" id="customRadio2" name="payment-radio-option" class="custom-control-input"
                   value="payment-credit-card">
            <label class="custom-control-label" for="customRadio2">{{__("Credit Card")}}</label>
        </div>
        @endif --}}
        @if(config('paypal.client_id') && config('paypal.secret'))
            <div class="custom-control custom-radio mb-1">
                <input type="radio" id="customRadio1" name="payment-radio-option" class="custom-control-input"
                       value="payment-paypal">
                <label class="custom-control-label" for="customRadio1">{{__("Paypal")}}</label>
            </div>
        @endif
        @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key'))
            <div class="custom-control custom-radio mb-1">
                <input type="radio" id="customRadio2" name="payment-radio-option" class="custom-control-input"
                       value="payment-stripe">
                <label class="custom-control-label stepTooltip" for="customRadio2" title=""
                       data-original-title="{{__('You need to login first')}}">{{__("Stripe")}}</label>
            </div>
        @endif
        @if(getSetting('payments.coinbase_api_key'))
            <div class="custom-control custom-radio mb-1">
                <input type="radio" id="customRadio3" name="payment-radio-option" class="custom-control-input"
                       value="payment-coinbase">
                <label class="custom-control-label stepTooltip" for="customRadio3" title="">{{__("Coinbase")}}</label>
            </div>
        @endif
        @if(getSetting('payments.nowpayments_api_key'))
            <div class="custom-control custom-radio mb-1">
                <input type="radio" id="customRadio5" name="payment-radio-option" class="custom-control-input"
                       value="payment-nowpayments">
                <label class="custom-control-label stepTooltip" for="customRadio5" title="">{{__("NowPayments Crypto")}}</label>
            </div>
        @endif
        @if(\App\Providers\PaymentsServiceProvider::ccbillCredentialsProvided())
            <div class="custom-control custom-radio mb-1">
                <input type="radio" id="customRadio6" name="payment-radio-option" class="custom-control-input"
                       value="payment-ccbill">
                <label class="custom-control-label stepTooltip" for="customRadio6" title="">{{__("CCBill")}}</label>
            </div>
        @endif
        @if(getSetting('payments.paystack_secret_key'))
            <div class="custom-control custom-radio mb-1">
                <input type="radio" id="customRadio7" name="payment-radio-option" class="custom-control-input"
                       value="payment-paystack">
                <label class="custom-control-label stepTooltip" for="customRadio7" title="">{{__("Paystack")}}</label>
            </div>
        @endif
        @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && getSetting('payments.stripe_oxxo_provider_enabled'))
                <div class="custom-control custom-radio mb-1">
                    <input type="radio" id="customRadio8" name="payment-radio-option" class="custom-control-input"
                           value="payment-oxxo">
                    <label class="custom-control-label stepTooltip" for="customRadio8" title="">{{__("Oxxo")}}</label>
                </div>
        @endif
        @if(getSetting('payments.allow_manual_payments'))
            <div class="custom-control custom-radio mb-1">
                <input type="radio" id="customRadio4" name="payment-radio-option" class="custom-control-input"
                       value="payment-manual">
                <label class="custom-control-label stepTooltip" for="customRadio4" title="">{{__("Bank transfer")}}</label>
            </div>
            <div class="manual-details d-none">
                <h5 class="mt-4 mb-3">{{__("Add payment details")}}</h5>
                                       
                @if(getSetting('payments.offline_payments_iban'))
                <div class="alert alert-primary text-white font-weight-bold" role="alert">
                    <p class="mb-0">{{__('Once confirmed, your credit will be available and you will be notified via email.')}}</p>
                    <ul class="mt-2 mb-2">
                        <li>{{__('IBAN')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_iban')}}</span></li>
                        <li>{{__('BIC/SWIFT')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_swift')}}</span></li>
                        <li>{{__('Bank name')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_bank_name')}}</span></li>
                        <li>{{__('Account owner')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_owner')}}</span></li>
                        <li>{{__('Account number')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_account_number')}}</span></li>
                        <li>{{__('Routing number')}}: <span class="font-weight-bold">{{getSetting('payments.offline_payments_routing_number')}}</span></li>
                    </ul>
                </div>
                @endif

                @if(getSetting('payments.offline_payments_custom_message_box'))
                    <div class="alert alert-primary text-white font-weight-bold" role="alert">
                        {!! getSetting('payments.offline_payments_custom_message_box') !!}
                    </div>
                @endif

                <div>
                    <label for="manualPaymentDescription" title="">{{__("Notes")}}</label>
                    <textarea class="form-control" id="manualPaymentDescription" rows="1"></textarea>
                    <span class="invalid-feedback" role="alert">
                        <strong>{{__("Payment notes are required")}}</strong>
                    </span>
                </div>
                <p class="mb-1 mt-2">{{__("Please attach clear photos with one the following: check, money order or bank transfer.")}}</p>
                <div class="dropzone-previews dropzone manual-payment-uploader w-100 ppl-0 pr-0 pt-1 pb-1 border rounded"></div>
                <small class="form-text text-muted mb-2">{{__("Allowed file types")}}: {{str_replace(',',', ',AttachmentHelper::filterExtensions('manualPayments'))}}.</small>
                <div class="text-danger invalid-files d-none">{{trans_choice('Please upload at least one file', (int)getSetting('payments.offline_payments_minimum_attachments_required'), ['num' => (int)getSetting('payments.offline_payments_minimum_attachments_required')])}}</div>
            </div>
        @endif
    </div>
    <div class="payment-error error text-danger d-none mt-3">{{__('Please select your payment method')}}</div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="card-number">{{__('Card Number')}}</label>
                <input type="text" class="form-control" id="card-number"  placeholder="{{__('Enter card number')}}" required>
            </div>
            <div class="form-group col-md-3">
                <label for="expiration-date">{{__('Expire Date')}}</label>
                <input type="text" class="form-control" id="expiration-date"   placeholder="{{__('MM/YY')}}" required>
            </div>
            <div class="form-group col-md-3">
                <label for="cvv">{{__('CVV')}}</label>
                <input type="text" class="form-control"  id="cvv" placeholder="{{__('CVV')}}" required>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
<h5>{{__('Personal Information')}}</h5>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="billing-firstname">{{__('First Name')}}</label>
        <input type="text" class="form-control" id="first-name"  placeholder="{{__('First name')}}" required>
    </div>
    <div class="form-group col-md-6">
        <label for="billing-lastname">{{__('Last Name')}}</label>
        <input type="text" class="form-control" id="last-name"  placeholder="{{__('Last name')}}" required>
    </div>

    <div class="form-group col-md-12">
        <label for="country">{{__('Country')}}</label>
        <input type="text" class="form-control" id="country"  placeholder="{{__('Enter country')}}" required>
    </div>
    <div class="form-group col-md-12">
        <label for="address">{{__('Address')}}</label>
        <input type="text" class="form-control" id="address"  placeholder="{{__('Enter address')}}" required>
    </div>
    <div class="form-group col-md-6">
        <label for="city">{{__('City')}}</label>
        <input type="text" class="form-control" id="city"  placeholder="{{__('Enter city')}}" required>
    </div>
    <div class="form-group col-md-6">
        <label for="state">{{__('State/Province')}}</label>
        <input type="text" class="form-control" id="state"  placeholder="{{__('Enter state/province')}}" required>
    </div>
    <div class="form-group col-md-6">
        <label for="zip-code">{{__('Zip Code')}}</label>
        <input type="number" class="form-control" id="zip-code"  placeholder="{{__('Enter zip code')}}" required>
    </div>
    <button class="btn btn-primary btn-block rounded mr-0 mt-4 deposit-continue-btn" type="submit">{{__('Add funds')}}
        <div class="spinner-border spinner-border-sm ml-2 d-none" role="status">
                                    <span class="sr-only">{{__('Loading...')}}</span>
         </div>
     </button>
</div>
@include('elements.uploaded-file-preview-template')



