import readXlsxFile from 'read-excel-file/node';

async function test() {
    const rows = await readXlsxFile('./Template_Import_Pegawai (1).xlsx');
    console.log("ROWS LENGTH:", rows.length);
    console.log("FIRST ROW (HEADERS):");
    console.log(rows[0]);
    console.log("SECOND ROW (DATA):");
    console.log(rows[1]);
}

test();
