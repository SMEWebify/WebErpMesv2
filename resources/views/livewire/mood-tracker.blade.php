<div>
    <div class="row">
        <div class="col-lg-5 btn-group" role="group" aria-label="Mood Selection">
            <button type="button" class="btn btn-outline-success {{ $mood == 'happy' ? 'active' : '' }}" wire:click="selectMood('happy')">
                😊 
            </button>
            <button type="button" class="btn btn-outline-info {{ $mood == 'neutral' ? 'active' : '' }}" wire:click="selectMood('neutral')">
                😐 
            </button>
            <button type="button" class="btn btn-outline-warning {{ $mood == 'sad' ? 'active' : '' }}" wire:click="selectMood('sad')">
                😢 
            </button>
        </div>

        <div class="col-lg-7">
            @if ($teamMoods->isEmpty())
                <p>{{ __('general_content.no_niko_niko_team_trans_key') }}</p>
            @else
                @foreach ($teamMoods->take(3) as $teamMood)
                    <div class="team-mood-item mr-2">
                        <img src="{{ Avatar::create($teamMood->user['name'])->toBase64() }}" />
                        @if($teamMood->mood == 'happy')
                            😊
                        @elseif($teamMood->mood == 'neutral')
                            😐
                        @elseif($teamMood->mood == 'sad')
                            😢
                        @endif
                    </div>
                @endforeach

                @if ($teamMoods->count() > 3)

                {{-- Themed --}}
                    <x-adminlte-modal id="NikoNikoModal" title="Niko Niko Team" theme="lime"
                    icon="fas fa-bolt" size='lg' disable-animations>
                        @foreach ($teamMoods as $teamMood)
                            <div class="team-mood-item mr-2">
                                <img src="{{ Avatar::create($teamMood->user['name'])->toBase64() }}" />
                                @if($teamMood->mood == 'happy')
                                    😊
                                @elseif($teamMood->mood == 'neutral')
                                    😐
                                @elseif($teamMood->mood == 'sad')
                                    😢
                                @endif
                            </div>
                        @endforeach
                    </x-adminlte-modal>
                    
                    <div class="team-mood-item mr-2">
                    {{-- Example button to open modal --}}
                    <x-adminlte-button label=">" data-toggle="modal" data-target="#NikoNikoModal" class="bg-lime"/>
                    
                </div>
                @endif
            @endif
        </div>
    </div>
</div>