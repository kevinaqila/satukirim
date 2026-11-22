export interface ExcelColumn<T = any> {
    header: string;
    key?: keyof T | string;
    align?: 'left' | 'center' | 'right';
    type?: 'text' | 'number' | 'currency' | 'date';
    isText?: boolean;
    formatter?: (item: T, index: number) => string | number | null | undefined;
    width?: number;
}

export interface ExportExcelOptions<T = any> {
    filename: string;
    sheetName?: string;
    title?: string;
    subtitle?: string;
    columns: ExcelColumn<T>[];
    data: T[];
}

/**
 * Fast in-memory CRC32 lookup table
 */
const makeCrcTable = (): Uint32Array => {
    const table = new Uint32Array(256);
    for (let n = 0; n < 256; n++) {
        let c = n;
        for (let k = 0; k < 8; k++) {
            c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        }
        table[n] = c >>> 0;
    }
    return table;
};

const crcTable = makeCrcTable();

const calculateCrc32 = (bytes: Uint8Array): number => {
    let crc = 0 ^ -1;
    for (let i = 0; i < bytes.length; i++) {
        crc = (crc >>> 8) ^ crcTable[(crc ^ bytes[i]) & 0xff];
    }
    return (crc ^ -1) >>> 0;
};

/**
 * Lightweight in-memory ZIP builder (Store compression / Method 0)
 * Fully compliant with PKZip and OpenXML (.xlsx) specification.
 */
class SimpleZip {
    private entries: {
        nameBytes: Uint8Array;
        data: Uint8Array;
        crc: number;
        size: number;
    }[] = [];
    private encoder = new TextEncoder();

    add(name: string, content: string | Uint8Array): void {
        const data = typeof content === 'string' ? this.encoder.encode(content) : content;
        this.entries.push({
            nameBytes: this.encoder.encode(name),
            data,
            crc: calculateCrc32(data),
            size: data.length,
        });
    }

    build(): Uint8Array {
        let totalSize = 0;
        for (const e of this.entries) {
            totalSize += 30 + e.nameBytes.length + e.size; // local header + data
            totalSize += 46 + e.nameBytes.length; // central directory header
        }
        totalSize += 22; // end of central directory (EOCD)

        const buffer = new Uint8Array(totalSize);
        const view = new DataView(buffer.buffer);
        let offset = 0;
        const localOffsets: number[] = [];

        // 1. Write Local File Headers + File Data
        for (const e of this.entries) {
            localOffsets.push(offset);

            view.setUint32(offset + 0, 0x04034b50, true); // signature
            view.setUint16(offset + 4, 20, true); // version needed (2.0)
            view.setUint16(offset + 6, 0x0800, true); // flags (UTF-8 filename)
            view.setUint16(offset + 8, 0, true); // compression (0 = Store)
            view.setUint16(offset + 10, 0, true); // time
            view.setUint16(offset + 12, 0, true); // date
            view.setUint32(offset + 14, e.crc, true); // crc32
            view.setUint32(offset + 18, e.size, true); // compressed size
            view.setUint32(offset + 22, e.size, true); // uncompressed size
            view.setUint16(offset + 26, e.nameBytes.length, true); // name length
            view.setUint16(offset + 28, 0, true); // extra length

            buffer.set(e.nameBytes, offset + 30);
            offset += 30 + e.nameBytes.length;

            buffer.set(e.data, offset);
            offset += e.size;
        }

        const cdOffset = offset;

        // 2. Write Central Directory Headers
        for (let i = 0; i < this.entries.length; i++) {
            const e = this.entries[i];
            const localOffset = localOffsets[i];

            view.setUint32(offset + 0, 0x02014b50, true); // signature
            view.setUint16(offset + 4, 20, true); // version made by
            view.setUint16(offset + 6, 20, true); // version needed
            view.setUint16(offset + 8, 0x0800, true); // flags (UTF-8)
            view.setUint16(offset + 10, 0, true); // compression (0)
            view.setUint16(offset + 12, 0, true); // time
            view.setUint16(offset + 14, 0, true); // date
            view.setUint32(offset + 16, e.crc, true); // crc32
            view.setUint32(offset + 20, e.size, true); // compressed size
            view.setUint32(offset + 24, e.size, true); // uncompressed size
            view.setUint16(offset + 28, e.nameBytes.length, true);
            view.setUint16(offset + 30, 0, true); // extra length
            view.setUint16(offset + 32, 0, true); // comment length
            view.setUint16(offset + 34, 0, true); // disk number start
            view.setUint16(offset + 36, 0, true); // internal attrs
            view.setUint32(offset + 38, 0, true); // external attrs
            view.setUint32(offset + 42, localOffset, true); // relative offset of local header

            buffer.set(e.nameBytes, offset + 46);
            offset += 46 + e.nameBytes.length;
        }

        const cdSize = offset - cdOffset;

        // 3. Write End of Central Directory Record
        view.setUint32(offset + 0, 0x06054b50, true); // signature
        view.setUint16(offset + 4, 0, true); // disk number
        view.setUint16(offset + 6, 0, true); // disk with cd
        view.setUint16(offset + 8, this.entries.length, true); // entries on this disk
        view.setUint16(offset + 10, this.entries.length, true); // total entries
        view.setUint32(offset + 12, cdSize, true); // cd size
        view.setUint32(offset + 16, cdOffset, true); // cd offset
        view.setUint16(offset + 20, 0, true); // comment length

        return buffer;
    }
}

/**
 * Convert 0-indexed column number to Excel column letter (0 -> A, 25 -> Z, 26 -> AA)
 */
const getColumnLetter = (colIdx: number): string => {
    let letter = '';
    let temp = colIdx + 1;
    while (temp > 0) {
        const mod = (temp - 1) % 26;
        letter = String.fromCharCode(65 + mod) + letter;
        temp = Math.floor((temp - mod) / 26);
    }
    return letter;
};

/**
 * Escape XML special characters and remove invalid control characters
 */
const escapeXml = (val: any): string => {
    if (val === null || val === undefined) return '';
    return String(val)
        .replace(/[\x00-\x08\x0B\x0C\x0E-\x1F]/g, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
};

/**
 * Generate native OpenXML (.xlsx) file and trigger instant download in browser
 */
export function exportToExcel<T = any>(options: ExportExcelOptions<T>): void {
    const { filename, title, subtitle, columns, data } = options;

    const zip = new SimpleZip();

    // 1. [Content_Types].xml
    zip.add(
        '[Content_Types].xml',
        `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>`,
    );

    // 2. _rels/.rels
    zip.add(
        '_rels/.rels',
        `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>`,
    );

    // 3. xl/_rels/workbook.xml.rels
    zip.add(
        'xl/_rels/workbook.xml.rels',
        `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>`,
    );

    // 4. xl/workbook.xml
    zip.add(
        'xl/workbook.xml',
        `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="${escapeXml(options.sheetName || 'Data')}" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>`,
    );

    // 5. xl/styles.xml
    // Fonts:
    // 0: Regular Calibri 11pt
    // 1: Bold Calibri 14pt (Brand Primary #853953)
    // 2: Muted Calibri 10pt (#666666)
    // 3: Bold Calibri 11pt White (Header)
    // Fills:
    // 0: none, 1: gray125, 2: header primary solid #853953 (FF853953), 3: zebra solid #FAFAFA (FFFAFAFA)
    // Borders:
    // 0: none, 1: thin light #DCDCDC (FFDCDCDC), 2: thin header #6B2E43 (FF6B2E43)
    // CellXfs:
    // 0: Default
    // 1: Title (Font 1, left)
    // 2: Subtitle (Font 2, left)
    // 3: Header center (Font 3, Fill 2, Border 2, align: center)
    // 4: Data left (Border 1, align: left)
    // 5: Data center (Border 1, align: center)
    // 6: Data right (Border 1, align: right)
    // 7: Data zebra left (Fill 3, Border 1, align: left)
    // 8: Data zebra center (Fill 3, Border 1, align: center)
    // 9: Data zebra right (Fill 3, Border 1, align: right)
    zip.add(
        'xl/styles.xml',
        `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="4">
    <font><name val="Calibri"/><sz val="11"/></font>
    <font><b/><color rgb="FF853953"/><name val="Calibri"/><sz val="14"/></font>
    <font><color rgb="FF666666"/><name val="Calibri"/><sz val="10"/></font>
    <font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/><sz val="11"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF853953"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFAFAFA"/></patternFill></fill>
  </fills>
  <borders count="3">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFDCDCDC"/></left>
      <right style="thin"><color rgb="FFDCDCDC"/></right>
      <top style="thin"><color rgb="FFDCDCDC"/></top>
      <bottom style="thin"><color rgb="FFDCDCDC"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FF6B2E43"/></left>
      <right style="thin"><color rgb="FF6B2E43"/></right>
      <top style="thin"><color rgb="FF6B2E43"/></top>
      <bottom style="thin"><color rgb="FF6B2E43"/></bottom>
    </border>
  </borders>
  <cellXfs count="10">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="3" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
  </cellXfs>
</styleSheet>`,
    );

    const colWidths = columns.map((c) => c.width ?? Math.max(12, c.header.length + 4));

    let currentRow = 1;
    let sheetData = '';

    if (title) {
        sheetData += `<row r="${currentRow}"><c r="A${currentRow}" s="1" t="inlineStr"><is><t>${escapeXml(title)}</t></is></c></row>`;
        currentRow++;
    }
    if (subtitle) {
        sheetData += `<row r="${currentRow}"><c r="A${currentRow}" s="2" t="inlineStr"><is><t>${escapeXml(subtitle)}</t></is></c></row>`;
        currentRow++;
    }
    if (title || subtitle) {
        currentRow++;
    }

    const headerRow = currentRow;
    sheetData += `<row r="${headerRow}" customHeight="1" ht="26">`;
    columns.forEach((col, cIdx) => {
        const cellRef = `${getColumnLetter(cIdx)}${headerRow}`;
        sheetData += `<c r="${cellRef}" s="3" t="inlineStr"><is><t>${escapeXml(col.header)}</t></is></c>`;
    });
    sheetData += `</row>`;
    currentRow++;

    data.forEach((row, rIdx) => {
        const isZebra = rIdx % 2 === 1;
        const rNum = currentRow;
        sheetData += `<row r="${rNum}" customHeight="1" ht="20">`;

        columns.forEach((col, cIdx) => {
            const cellRef = `${getColumnLetter(cIdx)}${rNum}`;
            let val: any = '';
            if (col.formatter) {
                val = col.formatter(row, rIdx);
            } else if (col.key && (row as any)[col.key] !== undefined) {
                val = (row as any)[col.key];
            }
            if (val === null || val === undefined) val = '';

            const strVal = String(val);
            if (col.width === undefined && strVal.length + 3 > colWidths[cIdx]) {
                colWidths[cIdx] = Math.min(45, strVal.length + 3);
            }

            let sIdx = 4;
            if (col.align === 'center') sIdx = 5;
            else if (col.align === 'right') sIdx = 6;
            if (isZebra) sIdx += 3;

            const isNumeric = col.type === 'number' && typeof val === 'number' && !isNaN(val);

            if (isNumeric) {
                sheetData += `<c r="${cellRef}" s="${sIdx}"><v>${val}</v></c>`;
            } else {
                // Keep text, phone numbers, and resi codes as inlineStr so Excel preserves leading zeros
                sheetData += `<c r="${cellRef}" s="${sIdx}" t="inlineStr"><is><t>${escapeXml(strVal)}</t></is></c>`;
            }
        });

        sheetData += `</row>`;
        currentRow++;
    });

    let colsXml = '<cols>';
    colWidths.forEach((w, idx) => {
        colsXml += `<col min="${idx + 1}" max="${idx + 1}" width="${w}" customWidth="1"/>`;
    });
    colsXml += '</cols>';

    const worksheetXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetViews>
    <sheetView tabSelected="1" workbookViewId="0" showGridLines="1"/>
  </sheetViews>
  ${colsXml}
  <sheetData>
    ${sheetData}
  </sheetData>
</worksheet>`;

    zip.add('xl/worksheets/sheet1.xml', worksheetXml);

    const xlsxBuffer = zip.build();
    const blob = new Blob([xlsxBuffer as any], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });

    const cleanFilename = filename.replace(/\.(xls|xlsx|csv)$/i, '');
    const finalFilename = `${cleanFilename}.xlsx`;

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = finalFilename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
