/**
 * Google Apps Script — Absensi HIMA TI Politeknik Hasnur
 * 
 * SETUP INSTRUCTIONS:
 * 1. Buka Google Spreadsheet baru
 * 2. Buat sheet dengan nama: "Data Absensi" dan "Rekap"
 * 3. Buka Extensions → Apps Script
 * 4. Hapus semua code default, paste seluruh code ini
 * 5. Klik Deploy → New Deployment
 * 6. Pilih "Web App"
 * 7. Execute as: "Me"
 * 8. Who has access: "Anyone"
 * 9. Deploy → Copy URL
 * 10. Paste URL ke Admin → Settings → Google Apps Script URL
 */

// ============================================
// MAIN HANDLER
// ============================================
function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    const action = data.action || 'appendAttendance';
    
    switch (action) {
      case 'appendAttendance':
        return appendAttendance(data.data);
      case 'savePhoto':
        return savePhotoToDrive(data.data);
      default:
        return ContentService.createTextOutput(JSON.stringify({
          success: false,
          message: 'Unknown action: ' + action
        })).setMimeType(ContentService.MimeType.JSON);
    }
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      message: 'Error: ' + error.message
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    success: true,
    message: 'Absensi HIMA TI API is running',
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

// ============================================
// APPEND ATTENDANCE DATA
// ============================================
function appendAttendance(dataArray) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = ss.getSheetByName('Data Absensi');
  
  // Create sheet if not exists
  if (!sheet) {
    sheet = ss.insertSheet('Data Absensi');
    // Add headers
    sheet.appendRow([
      'Tanggal', 'Jenis Rapat', 'Departemen Rapat', 'Nama Event/Agenda',
      'Fokus Bahasan', 'Nama Peserta', 'Departemen Peserta', 'Jabatan',
      'Status', 'Keterangan', 'Waktu Check-in', 'Mode Rapat', 'Timestamp'
    ]);
    
    // Format header
    const headerRange = sheet.getRange(1, 1, 1, 13);
    headerRange.setFontWeight('bold');
    headerRange.setBackground('#F5921B');
    headerRange.setFontColor('#FFFFFF');
    sheet.setFrozenRows(1);
  }
  
  // Append data rows
  if (Array.isArray(dataArray)) {
    dataArray.forEach(function(row) {
      sheet.appendRow([
        row.tanggal || '',
        row.jenis_rapat || '',
        row.departemen_rapat || '',
        row.nama_event || '',
        row.fokus_bahasan || '',
        row.nama_peserta || '',
        row.departemen_peserta || '',
        row.jabatan || '',
        row.status || '',
        row.keterangan || '',
        row.waktu_checkin || '',
        row.mode || '',
        new Date().toISOString()
      ]);
    });
  }
  
  return ContentService.createTextOutput(JSON.stringify({
    success: true,
    message: 'Data berhasil ditambahkan: ' + (dataArray ? dataArray.length : 0) + ' baris',
    rows_added: dataArray ? dataArray.length : 0
  })).setMimeType(ContentService.MimeType.JSON);
}

// ============================================
// SAVE PHOTO TO GOOGLE DRIVE
// ============================================
function savePhotoToDrive(data) {
  const folderId = data.folderId || '';
  const base64Image = data.image || '';
  const filename = data.filename || 'photo_' + Date.now() + '.jpg';
  
  if (!base64Image) {
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      message: 'No image data provided'
    })).setMimeType(ContentService.MimeType.JSON);
  }
  
  try {
    // Decode base64
    const imageBlob = Utilities.newBlob(
      Utilities.base64Decode(base64Image),
      'image/jpeg',
      filename
    );
    
    let folder;
    if (folderId) {
      try {
        folder = DriveApp.getFolderById(folderId);
      } catch (e) {
        // Create folder if not found
        folder = DriveApp.createFolder('Absensi HIMA TI - Dokumentasi');
      }
    } else {
      // Use or create default folder
      const folders = DriveApp.getFoldersByName('Absensi HIMA TI - Dokumentasi');
      if (folders.hasNext()) {
        folder = folders.next();
      } else {
        folder = DriveApp.createFolder('Absensi HIMA TI - Dokumentasi');
      }
    }
    
    const file = folder.createFile(imageBlob);
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
    
    return ContentService.createTextOutput(JSON.stringify({
      success: true,
      message: 'Photo saved successfully',
      file_url: file.getUrl(),
      file_id: file.getId()
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      message: 'Failed to save photo: ' + error.message
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

// ============================================
// UTILITY: Test Function
// ============================================
function testAppendAttendance() {
  const testData = [{
    tanggal: '2026-09-08',
    jenis_rapat: 'perdana',
    departemen_rapat: '',
    nama_event: 'Rapat Perdana Test',
    fokus_bahasan: 'Testing',
    nama_peserta: 'Test User',
    departemen_peserta: 'Inti',
    jabatan: 'Ketua Umum',
    status: 'hadir',
    keterangan: '',
    waktu_checkin: '10:00',
    mode: 'offline'
  }];
  
  const result = appendAttendance(testData);
  Logger.log(result.getContent());
}
