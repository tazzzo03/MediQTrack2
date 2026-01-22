{{-- resources/views/clinic/patients/_form.blade.php --}}
@php
  $isEdit = ($mode ?? '') === 'edit';
@endphp

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Full Name</label>
    <input type="text" name="name" class="form-control" id="{{ $isEdit ? 'edit_name' : 'create_name' }}" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">IC Number</label>
    <input type="text" name="ic_number" class="form-control" id="{{ $isEdit ? 'edit_ic_number' : 'create_ic_number' }}" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Date of Birth</label>
    <input type="date" name="dob" class="form-control" id="{{ $isEdit ? 'edit_dob' : 'create_dob' }}">
  </div>

  <div class="col-md-6">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" id="{{ $isEdit ? 'edit_email' : 'create_email' }}">
  </div>

  <div class="col-md-6">
    <label class="form-label">Phone Number</label>
    <input type="text" name="phone_number" class="form-control" id="{{ $isEdit ? 'edit_phone_number' : 'create_phone_number' }}">
  </div>

  <div class="col-md-6">
    <label class="form-label">Gender</label>
    <select name="gender" class="form-select" id="{{ $isEdit ? 'edit_gender' : 'create_gender' }}">
      <option value="">- Select -</option>
      <option value="male">Male</option>
      <option value="female">Female</option>
      <option value="other">Other</option>
    </select>
  </div>
</div>
