function paragraph(text, bold, center) {
 var p = Api.CreateParagraph(); p.SetSpacingAfter(160); p.SetJc(center ? 'center' : 'left');
 var r = Api.CreateRun(); r.SetFontFamily('Times New Roman'); r.SetFontSize(24); r.SetBold(!!bold); r.AddText(String(text)); p.AddElement(r); return p;
}
function field(doc, tag, placeholder) {
 var c = Api.CreateBlockLvlSdt(); c.SetTag(tag); doc.Push(c); c.GetContent().RemoveAllElements(); c.GetContent().Push(paragraph(placeholder));
}
function rowsTable(headers, rows) {
 var t = Api.CreateTable(headers.length,rows.length+1); t.SetWidth('percent',100);
 t.SetTableCellMarginTop(80); t.SetTableCellMarginBottom(80); t.SetTableCellMarginLeft(80); t.SetTableCellMarginRight(80);
 t.SetTableBorderTop('single',4,0,217,217,217); t.SetTableBorderBottom('single',4,0,217,217,217);
 t.SetTableBorderLeft('single',4,0,217,217,217); t.SetTableBorderRight('single',4,0,217,217,217);
 t.SetTableBorderInsideH('single',4,0,217,217,217); t.SetTableBorderInsideV('single',4,0,217,217,217);
 for(var i=0;i<=rows.length;i++) for(var j=0;j<headers.length;j++) {
  var c=t.GetRow(i).GetCell(j).GetContent(); c.RemoveAllElements(); c.Push(paragraph(i===0?headers[j]:rows[i-1][j],i===0));
 }
 return t;
}
function date(value) { if(!value) return '—'; var p=String(value).slice(0,10).split('-'); return p.length===3?p[2]+'.'+p[1]+'.'+p[0]:value; }

function title(doc,text) { var p=doc.GetElement(0); p.RemoveAllElements(); p.SetJc("center");p.SetSpacingAfter(240);var r=Api.CreateRun();r.SetFontFamily("Times New Roman");r.SetFontSize(28);r.SetBold(true);r.AddText(text);p.AddElement(r); }
