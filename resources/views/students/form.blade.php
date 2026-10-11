@extends('layouts.app')
@section('title',isset($student)?'Ubah data siswa':'Tambah siswa PKL')
@section('content')
<div class="page-heading"><div><span class="eyebrow blue">HUMAS · DATA PKL</span><h1>{{ isset($student)?'Ubah data siswa':'Tambah siswa PKL' }}</h1><p>Data ini digunakan sebagai sumber informasi sertifikat.</p></div></div>
<form class="form-card" method="post" action="{{ isset($student)?route('students.update',$student):route('students.store') }}">
    @csrf
    @if(isset($student))@method('put')@endif
    <div class="form-grid">
        <div class="field"><label for="name">Nama lengkap <span class="required">*</span></label><input class="input" id="name" name="name" value="{{ old('name',$student->name??'') }}" required></div>
        <div class="field"><label for="nis">Nomor induk siswa <span class="required">*</span></label><input class="input" id="nis" name="nis" value="{{ old('nis',$student->nis??'') }}" required></div>
        <div class="field"><label for="academic-year">Tahun pelajaran <span class="required">*</span></label><select class="select" id="academic-year" name="academic_year" required>@foreach($academicYears as $academicYear)<option value="{{ $academicYear }}" @selected(old('academic_year',$student->academic_year??$currentAcademicYear)===$academicYear)>{{ $academicYear }}</option>@endforeach</select></div>
        <div class="field"><label for="birth-place">Tempat lahir</label><input class="input" id="birth-place" name="birth_place" value="{{ old('birth_place',$student->birth_place??'') }}"></div>
        <div class="field"><label for="birth-date">Tanggal lahir</label><input class="input" id="birth-date" type="date" name="birth_date" value="{{ old('birth_date',isset($student)&&$student->birth_date?$student->birth_date->format('Y-m-d'):'') }}"></div>
        <div class="field"><label for="major">Konsentrasi keahlian <span class="required">*</span></label><select class="select" id="major" name="major" required><option value="">Pilih konsentrasi keahlian</option>
            @foreach(\App\Models\Student::MAJORS as $major)<option value="{{ $major }}" @selected(old('major',$student->major??'')===$major)>{{ $major }}</option>@endforeach
            @if(isset($student) && !in_array($student->major, \App\Models\Student::MAJORS, true))<option value="{{ $student->major }}" selected>{{ $student->major }} (data sebelumnya)</option>@endif
        </select></div>
        <div class="field"><label for="company-id">Perusahaan PKL</label><select class="select" id="company-id" name="company_id"><option value="">Pilih perusahaan</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)old('company_id',$student->company_id??'')===(string)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
        <div class="field"><label for="internship-start">Mulai PKL</label><input class="input" id="internship-start" type="date" name="internship_start" value="{{ old('internship_start',isset($student)&&$student->internship_start?$student->internship_start->format('Y-m-d'):'') }}"></div>
        <div class="field"><label for="internship-end">Selesai PKL</label><input class="input" id="internship-end" type="date" name="internship_end" value="{{ old('internship_end',isset($student)&&$student->internship_end?$student->internship_end->format('Y-m-d'):'') }}"></div>
        <div class="field"><label for="predicate">Predikat sertifikat</label><select class="select" id="predicate" name="predicate"><option value="">Belum dipilih</option><option value="Cukup" @selected(old('predicate',$student->predicate??'')==='Cukup')>Cukup</option><option value="Baik" @selected(old('predicate',$student->predicate??'')==='Baik')>Baik</option><option value="Sangat Baik" @selected(old('predicate',$student->predicate??'')==='Sangat Baik')>Sangat Baik</option></select><span class="field-hint">Pilih predikat di sini sebelum menerbitkan sertifikat.</span></div>
    </div>
    <div class="form-actions"><a class="button button-light" href="{{ route('students.index',['academic_year' => $student->academic_year??$currentAcademicYear]) }}">Batal</a><button class="button">Simpan data siswa</button></div>
</form>
@if(isset($student) && !$student->completed_at)
    <div class="danger-zone">
        <p class="field-hint">Hapus data siswa hanya jika pencatatannya memang keliru.</p>
        <form method="post" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus data siswa {{ $student->name }}? Tindakan ini tidak bisa dibatalkan.')">
            @csrf
            @method('delete')
            <button class="button button-danger" type="submit">Hapus data siswa</button>
        </form>
    </div>
@endif
@endsection
