@extends('adminlte::page')

@section('title', __('general_content.your_company_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.your_company_trans_key') }}</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#Settings" data-toggle="tab">{{ __('general_content.factory_settings_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#Announcement" data-toggle="tab">{{ __('general_content.announcements_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#Kanban" data-toggle="tab">{{ __('general_content.workflow_settings_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#EstimatedBudget" data-toggle="tab">{{ __('general_content.estimated_budget_trans_key') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }}</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="Settings">
                @include('include.alert-result')
                <form method="POST" action="{{ route('admin.factory.update') }}" enctype="multipart/form-data">
                @csrf
                
                    <x-adminlte-card title="{{ __('general_content.general_information_trans_key') }}" theme="primary" collapsible maximizable>
                        <div class="row">
                            <div class="col-12">
                                <label for="name">{{ __('general_content.name_company_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="name"  id="name" value="{{ $Factory->name }}" placeholder="{{ __('general_content.name_company_trans_key') }}">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <label for="address">{{ __('general_content.adress_name_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="address"  id="address" value="{{ $Factory->address }}"  placeholder="{{ __('general_content.adress_name_trans_key') }}">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="zipcode">{{ __('general_content.postal_code_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-map"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="zipcode"  id="zipcode"  value="{{ $Factory->zipcode }}"  placeholder="{{ __('general_content.postal_code_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="city">{{ __('general_content.capacity_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-city"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="city"  id="city" value="{{ $Factory->city }}"  placeholder="{{ __('general_content.capacity_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="country">{{ __('general_content.country_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-globe-africa"></i></span>
                                    </div>
                                    <select id="country" name="country" class="form-control">
                                        <option value="Afghanistan">Afghanistan</option>
                                        <option value="Åland Islands">Åland Islands</option>
                                        <option value="Albania">Albania</option>
                                        <option value="Algeria">Algeria</option>
                                        <option value="American Samoa">American Samoa</option>
                                        <option value="Andorra">Andorra</option>
                                        <option value="Angola">Angola</option>
                                        <option value="Anguilla">Anguilla</option>
                                        <option value="Antarctica">Antarctica</option>
                                        <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                                        <option value="Argentina">Argentina</option>
                                        <option value="Armenia">Armenia</option>
                                        <option value="Aruba">Aruba</option>
                                        <option value="Australia">Australia</option>
                                        <option value="Austria">Austria</option>
                                        <option value="Azerbaijan">Azerbaijan</option>
                                        <option value="Bahamas">Bahamas</option>
                                        <option value="Bahrain">Bahrain</option>
                                        <option value="Bangladesh">Bangladesh</option>
                                        <option value="Barbados">Barbados</option>
                                        <option value="Belarus">Belarus</option>
                                        <option value="Belgium">Belgium</option>
                                        <option value="Belize">Belize</option>
                                        <option value="Benin">Benin</option>
                                        <option value="Bermuda">Bermuda</option>
                                        <option value="Bhutan">Bhutan</option>
                                        <option value="Bolivia">Bolivia</option>
                                        <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                                        <option value="Botswana">Botswana</option>
                                        <option value="Bouvet Island">Bouvet Island</option>
                                        <option value="Brazil">Brazil</option>
                                        <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                                        <option value="Brunei Darussalam">Brunei Darussalam</option>
                                        <option value="Bulgaria">Bulgaria</option>
                                        <option value="Burkina Faso">Burkina Faso</option>
                                        <option value="Burundi">Burundi</option>
                                        <option value="Cambodia">Cambodia</option>
                                        <option value="Cameroon">Cameroon</option>
                                        <option value="Canada">Canada</option>
                                        <option value="Cape Verde">Cape Verde</option>
                                        <option value="Cayman Islands">Cayman Islands</option>
                                        <option value="Central African Republic">Central African Republic</option>
                                        <option value="Chad">Chad</option>
                                        <option value="Chile">Chile</option>
                                        <option value="China">China</option>
                                        <option value="Christmas Island">Christmas Island</option>
                                        <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
                                        <option value="Colombia">Colombia</option>
                                        <option value="Comoros">Comoros</option>
                                        <option value="Congo">Congo</option>
                                        <option value="Congo, The Democratic Republic of The">Congo, The Democratic Republic of The</option>
                                        <option value="Cook Islands">Cook Islands</option>
                                        <option value="Costa Rica">Costa Rica</option>
                                        <option value="Cote D'ivoire">Cote D'ivoire</option>
                                        <option value="Croatia">Croatia</option>
                                        <option value="Cuba">Cuba</option>
                                        <option value="Cyprus">Cyprus</option>
                                        <option value="Czech Republic">Czech Republic</option>
                                        <option value="Denmark">Denmark</option>
                                        <option value="Djibouti">Djibouti</option>
                                        <option value="Dominica">Dominica</option>
                                        <option value="Dominican Republic">Dominican Republic</option>
                                        <option value="Ecuador">Ecuador</option>
                                        <option value="Egypt">Egypt</option>
                                        <option value="El Salvador">El Salvador</option>
                                        <option value="Equatorial Guinea">Equatorial Guinea</option>
                                        <option value="Eritrea">Eritrea</option>
                                        <option value="Estonia">Estonia</option>
                                        <option value="Ethiopia">Ethiopia</option>
                                        <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
                                        <option value="Faroe Islands">Faroe Islands</option>
                                        <option value="Fiji">Fiji</option>
                                        <option value="Finland">Finland</option>
                                        <option value="France">France</option>
                                        <option value="French Guiana">French Guiana</option>
                                        <option value="French Polynesia">French Polynesia</option>
                                        <option value="French Southern Territories">French Southern Territories</option>
                                        <option value="Gabon">Gabon</option>
                                        <option value="Gambia">Gambia</option>
                                        <option value="Georgia">Georgia</option>
                                        <option value="Germany">Germany</option>
                                        <option value="Ghana">Ghana</option>
                                        <option value="Gibraltar">Gibraltar</option>
                                        <option value="Greece">Greece</option>
                                        <option value="Greenland">Greenland</option>
                                        <option value="Grenada">Grenada</option>
                                        <option value="Guadeloupe">Guadeloupe</option>
                                        <option value="Guam">Guam</option>
                                        <option value="Guatemala">Guatemala</option>
                                        <option value="Guernsey">Guernsey</option>
                                        <option value="Guinea">Guinea</option>
                                        <option value="Guinea-bissau">Guinea-bissau</option>
                                        <option value="Guyana">Guyana</option>
                                        <option value="Haiti">Haiti</option>
                                        <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>
                                        <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
                                        <option value="Honduras">Honduras</option>
                                        <option value="Hong Kong">Hong Kong</option>
                                        <option value="Hungary">Hungary</option>
                                        <option value="Iceland">Iceland</option>
                                        <option value="India">India</option>
                                        <option value="Indonesia">Indonesia</option>
                                        <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>
                                        <option value="Iraq">Iraq</option>
                                        <option value="Ireland">Ireland</option>
                                        <option value="Isle of Man">Isle of Man</option>
                                        <option value="Israel">Israel</option>
                                        <option value="Italy">Italy</option>
                                        <option value="Jamaica">Jamaica</option>
                                        <option value="Japan">Japan</option>
                                        <option value="Jersey">Jersey</option>
                                        <option value="Jordan">Jordan</option>
                                        <option value="Kazakhstan">Kazakhstan</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Kiribati">Kiribati</option>
                                        <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>
                                        <option value="Korea, Republic of">Korea, Republic of</option>
                                        <option value="Kuwait">Kuwait</option>
                                        <option value="Kyrgyzstan">Kyrgyzstan</option>
                                        <option value="Lao People's Democratic Republic">Lao People's Democratic Republic</option>
                                        <option value="Latvia">Latvia</option>
                                        <option value="Lebanon">Lebanon</option>
                                        <option value="Lesotho">Lesotho</option>
                                        <option value="Liberia">Liberia</option>
                                        <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
                                        <option value="Liechtenstein">Liechtenstein</option>
                                        <option value="Lithuania">Lithuania</option>
                                        <option value="Luxembourg">Luxembourg</option>
                                        <option value="Macao">Macao</option>
                                        <option value="Macedonia, The Former Yugoslav Republic of">Macedonia, The Former Yugoslav Republic of</option>
                                        <option value="Madagascar">Madagascar</option>
                                        <option value="Malawi">Malawi</option>
                                        <option value="Malaysia">Malaysia</option>
                                        <option value="Maldives">Maldives</option>
                                        <option value="Mali">Mali</option>
                                        <option value="Malta">Malta</option>
                                        <option value="Marshall Islands">Marshall Islands</option>
                                        <option value="Martinique">Martinique</option>
                                        <option value="Mauritania">Mauritania</option>
                                        <option value="Mauritius">Mauritius</option>
                                        <option value="Mayotte">Mayotte</option>
                                        <option value="Mexico">Mexico</option>
                                        <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
                                        <option value="Moldova, Republic of">Moldova, Republic of</option>
                                        <option value="Monaco">Monaco</option>
                                        <option value="Mongolia">Mongolia</option>
                                        <option value="Montenegro">Montenegro</option>
                                        <option value="Montserrat">Montserrat</option>
                                        <option value="Morocco">Morocco</option>
                                        <option value="Mozambique">Mozambique</option>
                                        <option value="Myanmar">Myanmar</option>
                                        <option value="Namibia">Namibia</option>
                                        <option value="Nauru">Nauru</option>
                                        <option value="Nepal">Nepal</option>
                                        <option value="Netherlands">Netherlands</option>
                                        <option value="Netherlands Antilles">Netherlands Antilles</option>
                                        <option value="New Caledonia">New Caledonia</option>
                                        <option value="New Zealand">New Zealand</option>
                                        <option value="Nicaragua">Nicaragua</option>
                                        <option value="Niger">Niger</option>
                                        <option value="Nigeria">Nigeria</option>
                                        <option value="Niue">Niue</option>
                                        <option value="Norfolk Island">Norfolk Island</option>
                                        <option value="Northern Mariana Islands">Northern Mariana Islands</option>
                                        <option value="Norway">Norway</option>
                                        <option value="Oman">Oman</option>
                                        <option value="Pakistan">Pakistan</option>
                                        <option value="Palau">Palau</option>
                                        <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>
                                        <option value="Panama">Panama</option>
                                        <option value="Papua New Guinea">Papua New Guinea</option>
                                        <option value="Paraguay">Paraguay</option>
                                        <option value="Peru">Peru</option>
                                        <option value="Philippines">Philippines</option>
                                        <option value="Pitcairn">Pitcairn</option>
                                        <option value="Poland">Poland</option>
                                        <option value="Portugal">Portugal</option>
                                        <option value="Puerto Rico">Puerto Rico</option>
                                        <option value="Qatar">Qatar</option>
                                        <option value="Reunion">Reunion</option>
                                        <option value="Romania">Romania</option>
                                        <option value="Russian Federation">Russian Federation</option>
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Saint Helena">Saint Helena</option>
                                        <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                                        <option value="Saint Lucia">Saint Lucia</option>
                                        <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
                                        <option value="Saint Vincent and The Grenadines">Saint Vincent and The Grenadines</option>
                                        <option value="Samoa">Samoa</option>
                                        <option value="San Marino">San Marino</option>
                                        <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                                        <option value="Saudi Arabia">Saudi Arabia</option>
                                        <option value="Senegal">Senegal</option>
                                        <option value="Serbia">Serbia</option>
                                        <option value="Seychelles">Seychelles</option>
                                        <option value="Sierra Leone">Sierra Leone</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="Slovakia">Slovakia</option>
                                        <option value="Slovenia">Slovenia</option>
                                        <option value="Solomon Islands">Solomon Islands</option>
                                        <option value="Somalia">Somalia</option>
                                        <option value="South Africa">South Africa</option>
                                        <option value="South Georgia and The South Sandwich Islands">South Georgia and The South Sandwich Islands</option>
                                        <option value="Spain">Spain</option>
                                        <option value="Sri Lanka">Sri Lanka</option>
                                        <option value="Sudan">Sudan</option>
                                        <option value="Suriname">Suriname</option>
                                        <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
                                        <option value="Swaziland">Swaziland</option>
                                        <option value="Sweden">Sweden</option>
                                        <option value="Switzerland">Switzerland</option>
                                        <option value="Syrian Arab Republic">Syrian Arab Republic</option>
                                        <option value="Taiwan">Taiwan</option>
                                        <option value="Tajikistan">Tajikistan</option>
                                        <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
                                        <option value="Thailand">Thailand</option>
                                        <option value="Timor-leste">Timor-leste</option>
                                        <option value="Togo">Togo</option>
                                        <option value="Tokelau">Tokelau</option>
                                        <option value="Tonga">Tonga</option>
                                        <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                                        <option value="Tunisia">Tunisia</option>
                                        <option value="Turkey">Turkey</option>
                                        <option value="Turkmenistan">Turkmenistan</option>
                                        <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
                                        <option value="Tuvalu">Tuvalu</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Ukraine">Ukraine</option>
                                        <option value="United Arab Emirates">United Arab Emirates</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="United States">United States</option>
                                        <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                                        <option value="Uruguay">Uruguay</option>
                                        <option value="Uzbekistan">Uzbekistan</option>
                                        <option value="Vanuatu">Vanuatu</option>
                                        <option value="Venezuela">Venezuela</option>
                                        <option value="Viet Nam">Viet Nam</option>
                                        <option value="Virgin Islands, British">Virgin Islands, British</option>
                                        <option value="Virgin Islands, U.S.">Virgin Islands, U.S.</option>
                                        <option value="Wallis and Futuna">Wallis and Futuna</option>
                                        <option value="Western Sahara">Western Sahara</option>
                                        <option value="Yemen">Yemen</option>
                                        <option value="Zambia">Zambia</option>
                                        <option value="Zimbabwe">Zimbabwe</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="phone_number">{{ __('general_content.phone_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="phone_number"  id="phone_number"  value="{{ $Factory->phone_number }}"  placeholder="{{ __('general_content.phone_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="mail">{{ __('general_content.email_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">@</span>
                                    </div>
                                    <input type="email" class="form-control" name="mail"  id="mail" value="{{ $Factory->mail }}"  placeholder="{{ __('general_content.email_trans_key') }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="web_site">{{ __('general_content.web_link_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                                    </div>
                                    <input type="text" class="form-control"  name="web_site" id="web_site" value="{{ $Factory->web_site }}" placeholder="{{ __('general_content.web_link_trans_key') }}">
                                </div>
                            </div>
                        </div>
                    </x-adminlte-card>

                    <x-adminlte-card title="{{ __('general_content.administrative_information_trans_key') }}" theme="secondary" collapsible maximizable>
                        <div class="row">
                            <div class="col-3">
                                <input type="text" class="form-control" name="siren" id="siren" value="{{ $Factory->siren }}" placeholder="{{ __('general_content.siren_trans_key') }}">
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="nat_regis_num" id="nat_regis_num" value="{{ $Factory->nat_regis_num }}" placeholder="{{ __('general_content.nat_regis_number_trans_key') }}">
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="vat_num" id="vat_num" value="{{ $Factory->vat_num }}" placeholder="{{ __('general_content.vat_number_trans_key') }}">
                            </div>
                            <div class="col-3">
                                <input type="text" class="form-control" name="share_capital" id="share_capital" value="{{ $Factory->share_capital }}" placeholder="{{ __('general_content.share_capital_trans_key') }}">
                            </div>
                        </div>
                    </x-adminlte-card>

                    <x-adminlte-card title="{{ __('general_content.default_value_trans_key') }}" theme="info" collapsible maximizable>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="add_day_validity_quote">{{ __('general_content.add_value_day_offer_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+</span>
                                    </div>
                                    <input type="number" class="form-control" name="add_day_validity_quote" id="add_day_validity_quote" value="{{ $Factory->add_day_validity_quote }}" >
                                    <div class="input-group-append">
                                        <span class="input-group-text">{{ __('general_content.day_trans_key') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="add_delivery_delay_order">{{ __('general_content.add_value_day_delivery_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+</span>
                                    </div>
                                    <input type="number" class="form-control" name="add_delivery_delay_order" id="add_delivery_delay_order" value="{{ $Factory->add_delivery_delay_order }}" >
                                    <div class="input-group-append">
                                        <span class="input-group-text">{{ __('general_content.day_trans_key') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-8">
                                <label for="accounting_vats_id">{{ __('general_content.vat_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                    </div>
                                    <select class="form-control"  name="accounting_vats_id" id="accounting_vats_id">
                                        <option value="" >{{ __('general_content.select_vat_trans_key') }}</option>
                                        @foreach ($VATSelect as $item)
                                        <option value="{{ $item->id }}" @if($item->id == $Factory->accounting_vats_id ) Selected @endif >{{ $item->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-8">
                                <label for="curency">{{ __('general_content.curency_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <select class="form-control"  name="curency" id="curency" >
                                        <option value="USD" @if('USD' == $Factory->curency ) Selected @endif>United States Dollars</option>
                                        <option value="EUR" @if('EUR' == $Factory->curency ) Selected @endif>Euro</option>
                                        <option value="GBP" @if('GBP' == $Factory->curency ) Selected @endif>United Kingdom Pounds</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </x-adminlte-card>

                    <x-adminlte-card title="{{ __('general_content.repots_setting_trans_key') }}" theme="teal" collapsible maximizable>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <div class="form-group">
                                    <label for="pdf_header_font_color">{{ __('general_content.header_font_color_trans_key') }}</label>
                                    <input type="color" class="form-control"  name="pdf_header_font_color" id="pdf_header_font_color" value="{{ $Factory->pdf_header_font_color }}">
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="public_link_cgv">{{ __('general_content.public_link_cgv_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                                    </div>
                                    <select class="form-control"  name="public_link_cgv" id="public_link_cgv" >
                                        <option value="1"  @if('1' == $Factory->public_link_cgv ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                                        <option value="2" @if('2' == $Factory->public_link_cgv ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="add_cgv_to_pdf">{{ __('general_content.add_cgv_to_quote_order_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                                    </div>
                                    <select class="form-control"  name="add_cgv_to_pdf" id="add_cgv_to_pdf" >
                                        <option value="1" @if('1' == $Factory->add_cgv_to_pdf ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                                        <option value="2" @if('2' == $Factory->add_cgv_to_pdf ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-image"></i></span>
                                    </div>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="cgv_file" id="cgv_file">
                                        <label class="custom-file-label" for="cgv_file">{{ __('general_content.choose_file_trans_key') }}  (pdf | max: 10 240 Ko)</label>
                                    </div>
                                </div>
                            </div>
                            @if( $Factory->cgv_file)
                            <div class="col-3 text-center">
                                <a class="btn btn-info btn-sm " href="{{ asset('/cgv/factory/'. $Factory->cgv_file) }}" target="_blank">{{ __('general_content.show_current_file_trans_key') }}</a>
                            </div>
                            @endif
                        </div>
                    </x-adminlte-card>

                    <x-adminlte-card title="{{ __('general_content.picture_trans_key') }}" theme="warning" collapsible maximizable>
                        @if($Factory->picture)
                        <div class="row">
                            <img src="{{ asset('/images/factory/'. $Factory->picture) }}" alt="Product Image">
                        </div>
                        @endif
                        <div class="row">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-image"></i></span>
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="picture" id="picture">
                                    <label class="custom-file-label" for="picture">{{ __('general_content.choose_file_trans_key') }}  (peg,png,jpg,gif,svg | max: 10 240 Ko)</label>
                                </div>
                            </div>
                        </div>
                    </x-adminlte-card>

                    <x-adminlte-card title="{{ __('general_content.manufacturing_information_trans_key') }}" theme="purple" collapsible maximizable>
                        <div class="col-8">
                            <label for="task_barre_code">{{ __('general_content.bare_code_type_trans_key') }}</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                </div>
                                <select class="form-control"  name="task_barre_code" id="task_barre_code">
                                    <option value="EAN2" @if('EAN2' == $Factory->task_barre_code ) Selected @endif >EAN2</option>
                                    <option value="EAN5" @if('EAN5' == $Factory->task_barre_code ) Selected @endif >EAN5</option>
                                    <option value="EAN8" @if('EAN8' == $Factory->task_barre_code ) Selected @endif >EAN8</option>
                                    <option value="EAN13" @if('EAN13' == $Factory->task_barre_code ) Selected @endif >EAN13</option>
                                    <option value="UPCA" @if('UPCA' == $Factory->task_barre_code ) Selected @endif >UPCA</option>
                                    <option value="UPCE" @if('UPCE' == $Factory->task_barre_code ) Selected @endif >UPCE</option>
                                    <option value="CODE11" @if('CODE11' == $Factory->task_barre_code ) Selected @endif >CODE11</option>
                                    <option value="C39" @if('C39' == $Factory->task_barre_code ) Selected @endif >C39</option>
                                </select>
                            </div>
                        </div>
                    </x-adminlte-card>
                    
                    <div class="modal-footer">
                        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                    </div>
                </form>
            </div>
            <div class="tab-pane " id="Announcement">
                @include('include.alert-result')
                <form method="POST" action="{{ route('admin.factory.announcement.create') }}" enctype="multipart/form-data">
                    @csrf
                    <x-adminlte-card title="{{ __('general_content.make_an_announcement_trans_key') }}" theme="primary" maximizable>
                        <div class="row">
                            <div class="col-3">
                                <label for="title">{{ __('general_content.title_trans_key') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="title"  id="title" placeholder="{{ __('general_content.title_trans_key') }}">
                                </div>
                            </div>
                            <div class="col-9">
                                <x-FormTextareaComment  comment="..." />
                            </div>
                        </div>
                        <x-slot name="footerSlot">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                        </x-slot>
                    </x-adminlte-card>
                </form>
                
                <x-adminlte-card title="{{ __('general_content.announcements_trans_key') }}" theme="secondary" maximizable>
                    <div class="table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('general_content.title_trans_key') }}</th>
                                    <th>{{ __('general_content.text_trans_key') }}</th>
                                    <th>{{__('general_content.action_trans_key') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($AnnouncementLines as $AnnouncementLine)
                                <tr>
                                    <td>{{ $AnnouncementLine->title }}</td>
                                    <td>{{ $AnnouncementLine->comment }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.factory.announcement.delete', ['id' => $AnnouncementLine->id])}}" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                    <x-EmptyDataLine col="3" text="{{ __('general_content.no_data_trans_key') }}"  />
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{{ __('general_content.title_trans_key') }}</th>
                                    <th>{{ __('general_content.text_trans_key') }}</th>
                                    <th>{{__('general_content.action_trans_key') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-adminlte-card>
            </div>
            <div class="tab-pane " id="Kanban">
                <x-InfocalloutComponent note="{{__('general_content.kanban_setting_note_trans_key') }}"  />
                @livewire('kanban-setting')
            </div>
            <div class="tab-pane " id="EstimatedBudget">
                <x-InfocalloutComponent note="Used for dashboard chart."  />
                @livewire('estimated-budget')
            </div>
            <div class="tab-pane " id="CustomFields">
                <div class="row">
                    <div class="col-md-6">
                        <x-adminlte-card title="{{ __('general_content.families_trans_key') }}" theme="primary" maximizable>
                            <div class="table-responsive p-0">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{ __('general_content.name_field_trans_key') }}</th>
                                            <th>{{ __('general_content.type_field_trans_key') }} </th>
                                            <th>{{ __('general_content.entity_type_trans_key') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($CustomFields as $CustomField)
                                            <tr>
                                                <td>{{ $CustomField->name }}</td>
                                                <td>{{ $CustomField->type }}</td>
                                                <td>{{ $CustomField->related_type }}</td>
                                                <td></td>
                                            </tr>
                                        @empty
                                            <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>{{ __('general_content.name_field_trans_key') }}</th>
                                            <th>{{ __('general_content.type_field_trans_key') }} </th>
                                            <th>{{ __('general_content.entity_type_trans_key') }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </x-adminlte-card>
                    </div>
                    <div class="col-md-6">
                        <x-adminlte-card title="{{ __('general_content.new_family_trans_key') }}" theme="secondary" maximizable>
                            <form  method="POST" action="{{ route('admin.factory.custom.field.store') }}" class="form-horizontal">
                                @csrf
                                <div class="form-group">
                                    <label for="code">{{ __('general_content.name_field_trans_key') }} :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="label">{{ __('general_content.type_field_trans_key') }}  :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                        </div>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="checkbox">Checkbox</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="methods_services_id">{{ __('general_content.entity_type_trans_key') }}  :</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-list"></i></span>
                                        </div>
                                        <select class="form-control" id="related_type" name="related_type" required>
                                            <option value="quote">{{ __('general_content.quote_trans_key') }}</option>
                                            <option value="order">{{ __('general_content.orders_trans_key') }}</option>
                                            <option value="delivery">{{ __('general_content.delivery_notes_trans_key') }}</option>
                                            <option value="invoice">{{ __('general_content.invoice_trans_key') }}</option>
                                            <option value="purchase">{{ __('general_content.purchase_order_trans_key') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                                </div>
                            </form>
                        </x-adminlte-card>
                    </div>
                    <!-- /.card secondary -->
                </div>
                <!-- /.row -->
            </div>
        </div>
    </div>
</div>
@stop

@section('plugins.BootstrapSwitch', true)

@section('css')
@stop

@section('js')
@stop