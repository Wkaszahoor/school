import XLSX from 'xlsx';
import fs from 'fs';

const wb = XLSX.readFile('Data_Base_of_Kort_Students.xls');
const ws = wb.Sheets[wb.SheetNames[0]];
const data = XLSX.utils.sheet_to_json(ws);

const excelDateToYMD = (serialDate) => {
    if (!serialDate || typeof serialDate !== 'number') return '';
    const date = new Date((serialDate - 25569) * 86400 * 1000);
    return date.toISOString().split('T')[0];
};

const converted = data.map(row => ({
    admission_no: row.ID ? 'KRT' + String(row.ID).padStart(4, '0') : '',
    full_name: row.Student_Name || '',
    student_cnic: row.Student_CNIC || '',
    father_name: row.Father_Name || '',
    father_cnic: row.Father_CNIC || '',
    mother_name: row.Mother_Name || '',
    mother_cnic: row.Mother_CNIC || '',
    guardian_name: row["Guardian's_Name"] || '',
    guardian_cnic: row["Guardian's_CNIC"] || '',
    guardian_address: row.Address || '',
    phone: row.Contact_Number || '',
    dob: excelDateToYMD(row.Date_of_Birth),
    favorite_color: row.Favorite_color || '',
    favorite_food: row.Favorite_Food || '',
    favorite_subject: row.Favorite_Subject || '',
    ambition: row.Ambition || '',
    class_name: row.Class || '',
    group_stream: row.Course || 'general',
    semester: row.Semester || '',
    join_date_kort: excelDateToYMD(row.Joining_Date),
    gender: (row.Gender || 'male').toLowerCase(),
    is_active: row.Status !== 'Inactive' ? 1 : 0,
    reason_left_kort: row.Reason_LeftKORT || ''
})).filter(s => s.full_name && s.dob);

fs.writeFileSync('storage/app/students_import.json', JSON.stringify(converted, null, 2));
console.log('Wrote ' + converted.length + ' students to storage/app/students_import.json');
