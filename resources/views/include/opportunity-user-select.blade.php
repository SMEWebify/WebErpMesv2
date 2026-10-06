{{-- Responsable d'une activité / d'un événement d'opportunité. Attend $UsersSelect et $selected. --}}
<div class="form-group">
  <label for="user_id">{{ __('general_content.assigned_user_trans_key') }}</label>
  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-user"></i></span>
    </div>
    <select class="form-control" name="user_id">
      <option value="">—</option>
      @foreach ($UsersSelect as $SelectUser)
        <option value="{{ $SelectUser->id }}" @selected($SelectUser->id == $selected)>{{ $SelectUser->name }}</option>
      @endforeach
    </select>
  </div>
</div>
