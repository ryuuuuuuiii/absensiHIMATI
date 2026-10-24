@extends('layouts.app')

@section('title', 'Generate QR Code')

@section('page_icon')
    <i class="fas fa-qrcode"></i>
@endsection

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <div class="card mb-6">
        <h3 class="card-title mb-4"><i class="fas fa-qrcode"></i> Generate & Download QR Code Anggota</h3>
        
        <div class="alert alert-info mb-6">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Informasi:</strong><br>
                <small>Sistem dapat mengenerate ulang file gambar QR Code untuk seluruh anggota secara otomatis menggunakan resolusi tinggi (500x500px), atau langsung mendownload arsip ZIP berisi semua QR code anggota terkelompok per departemen.</small>
            </div>
        </div>
        
        <div style="display: flex; gap: var(--space-4); flex-wrap: wrap; align-items: center;">
            <form action="{{ route('members.generate_qr.all') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-primary" onclick="this.innerHTML='<span class=spinner></span> Sedang Generate...'; this.disabled=true; this.form.submit();">
                    <i class="fas fa-magic"></i> Generate Semua QR Code
                </button>
            </form>
            
            <a href="{{ route('members.download_qr_zip') }}" class="btn btn-success">
                <i class="fas fa-file-archive"></i> Download Semua QR (.ZIP)
            </a>
            
            <a href="{{ route('members.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Anggota
            </a>
        </div>
    </div>
    
    <!-- QR Status List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-check"></i> Status & File QR Code</h3>
            <span class="badge badge-primary">{{ $members->count() }} anggota</span>
        </div>
        
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Departemen</th>
                        <th>Kode QR</th>
                        <th>File Fisik</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $m)
                    @php
                        $hasFile = $m->qr_image && file_exists(public_path('assets/img/qrcodes/' . $m->qr_image));
                    @endphp
                    <tr>
                        <td><strong>{{ $m->nama_lengkap }}</strong></td>
                        <td><span class="badge badge-dept">{{ $m->departemen }}</span></td>
                        <td style="font-size: var(--font-xs); font-family: monospace;">{{ $m->qr_code }}</td>
                        <td>
                            @if ($hasFile)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Siap</span>
                            @else
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Belum Dibuat</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('members.download_qr_single', $m->id) }}" class="btn btn-sm btn-outline-primary" title="Download file QR">
                                <i class="fas fa-download"></i> Unduh
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
