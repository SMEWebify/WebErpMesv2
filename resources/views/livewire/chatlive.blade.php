<div class="card card-secondary">
    <div class="card-header">
        <h3 class="card-title">Direct Chat</h3>
    </div>
        
    <div class="card-body">
        <div class="direct-chat-messages">
            @php 
                $styleChatMsg="";
                $styleName="left";
                $styletimestamp="right";
                $lastid=0;
                $i = 1;
            @endphp

            @forelse ($ChatMessages as $ChatMessage)
                @php
                    if($ChatMessage->user_id != $lastid){
                        $i++;
                        $lastid = $ChatMessage->user_id;
                    }

                    if($i%2 == 1){
                        $styleChatMsg="right";
                        $styleName="right";
                        $styletimestamp="left";
                    }
                    else{
                        $styleChatMsg="left";
                        $styleName="left";
                        $styletimestamp="right";
                    }
                @endphp
            <div class="direct-chat-msg {{ $styleChatMsg }}">
                <div class="direct-chat-infos clearfix">
                    <span class="direct-chat-name float-{{ $styleName }}">
                        @if($ChatMessage->user_id)
                            {{ $ChatMessage->User->name }}
                        @else
                            Guest
                        @endif
                    </span>
                    <span class="direct-chat-timestamp float-{{ $styletimestamp }}">{{ $ChatMessage->GetPrettyCreatedAttribute() }}</span>
                </div>
            
                <!--<img class="direct-chat-img" src="dist/img/user1-128x128.jpg" alt="message user image">-->
            
                <div class="direct-chat-text">
                    {{ $ChatMessage->label }}     
                </div>
            </div>

            @empty
            <div class="direct-chat-infos clearfix">
                <span class="direct-chat-name float-left">system</span>
                <span class="direct-chat-timestamp float-right">-</span>
            </div>
        
            <!--<img class="direct-chat-img" src="dist/img/user1-128x128.jpg" alt="message user image">-->
        
            <div class="direct-chat-text">
                {{ __('general_content.no_data_trans_key') }}.
            </div>
        @endforelse
        </div>
    </div> 
        
    <div class="input-group">
        <input type="text" wire:model.live="label" name="label"  id="label" placeholder="Chat ..." class="form-control">
        <span class="input-group-append">
            <button type="button" wire:click="storeMessage({{ $idItem }}, '{{ $Class }}')" class="btn btn-danger">{{ __('general_content.add_trans_key') }}</button>
        </span>
    </div>
    @error('chatlabel') <span class="text-danger">{{ $message }}<br/></span>@enderror
</div>