var leave=data.leave||{}, person=data.person, manager=data.manager||{};
function value(v){return v||'________________';}
function longDate(v){if(!v)return '________________';var a=String(v).slice(0,10).split('-');var months=['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];return Number(a[2])+' '+months[Number(a[1])-1]+' '+a[0]+' года';}
var days=Math.round((new Date(leave.end+'T12:00:00Z')-new Date(leave.start+'T12:00:00Z'))/86400000)+1;
var who=leave.applicant_dative;
var leaveType=leave.type||'ежегодного отпуска';
var types={'ежегодный отпуск':'ежегодного отпуска','отпуск по личным обстоятельствам':'отпуска по личным обстоятельствам'};leaveType=types[leaveType]||leaveType;
var request=who?'Прошу Вашего ходатайства о предоставлении мне, '+who+', '+leaveType:'Я, '+[person.rank_name,person.name,person.position].filter(Boolean).join(', ')+', прошу Вашего ходатайства о предоставлении мне '+leaveType;
request+=(leave.reason?' в связи с '+leave.reason:'')+' продолжительностью '+days+' суток с '+longDate(leave.start)+' по '+longDate(leave.end)+'.';
var report={
 recipient:leave.recipient||('Руководителю: '+value(manager.position)+' '+value(manager.name)),
 request:request,
 following:leave.following_start?'С последующим убытием в летний каникулярный отпуск за '+String(leave.following_start).slice(0,4)+' год с '+longDate(leave.following_start)+' по '+longDate(leave.following_end)+' без прибытия между отпусками.':'',
 location:'Место проведения отпуска: '+value(leave.location)+'. К месту проведения отпуска буду следовать '+value(leave.transport)+' транспортом.',
 return:'Порядок прибытия к месту службы '+longDate(leave.return_date||leave.following_end||leave.end)+' к '+value(leave.return_time)+' в '+value(leave.return_place||person.department)+' мною усвоен. Все нормы довольствия по состоянию на день убытия в отпуск до меня доведены в полном объёме.',
 sign_role:value(person.position),sign_rank:value(person.rank_name),sign_name:person.name+'    __________________',sign_date:longDate(leave.report_date||data.generated_at),
 endorsement_recipient:value(leave.endorsement_recipient),
 endorsement_text:'Ходатайствую об удовлетворении просьбы, изложенной в рапорте. Заявитель: '+person.name+'. Прошу оформить установленным порядком.',
 endorsement_author:leave.endorsement_author||[manager.position,manager.rank_name,manager.name].filter(Boolean).join(', ')||'________________',
 final_recipient:value(leave.final_recipient),
 final_text:'Ходатайствую об удовлетворении просьбы, поддержанной первым согласующим. Согласующий: '+value(leave.endorsement_author||manager.name)+'. Прошу оформить установленным порядком.',
 final_author:value(leave.final_author)
};
var doc=Api.GetDocument(); var controls=doc.GetAllContentControls();
for(var k=0;k<controls.length;k++) {
 var c=controls[k], tag=c.GetTag(), content=c.GetContent();
 if(tag.indexOf('leave.')===0 && Object.prototype.hasOwnProperty.call(report,tag.slice(6))) {
  var p=content.GetElement(0);p.RemoveAllElements();var run=Api.CreateRun();run.SetFontFamily('Times New Roman');run.SetFontSize(24);run.AddText(report[tag.slice(6)]);p.AddElement(run);continue;
 }
 if(['person','manager','leave','signature','awards','service'].indexOf(tag)<0 && tag.indexOf('person.')!==0) continue;
 content.RemoveAllElements();
 if(tag.indexOf('person.')===0) {content.Push(paragraph(data.person[tag.slice(7)]||'—'));continue;}
 if(tag==='person') content.Push(paragraph([data.person.rank_name,data.person.name,data.person.position,'Личный номер: '+data.person.personnel_number].filter(Boolean).join(', ')));
 else if(tag==='manager') content.Push(paragraph('Руководителю: '+(data.manager?data.manager.name:'не указан')));
 else if(tag==='leave') content.Push(paragraph('Прошу предоставить '+data.leave.type+' с '+date(data.leave.start)+' по '+date(data.leave.end)+'.'));
 else if(tag==='signature') content.Push(paragraph('Дата: '+date(data.generated_at)+'    Подпись: __________________'));
 else if(tag==='awards') {
  var rows=data.children.HR_Awards.map(function(a){return [a.name,date(a.award_date),a.order_number||'—'];});
  content.Push(rows.length?rowsTable(['Награда','Дата','Приказ'],rows):paragraph('Наград нет.'));
 } else if(tag==='service') {
  var rows=data.children.HR_Service.map(function(a){return [date(a.start_date),a.end_date?date(a.end_date):'по настоящее время',a.name,a.position||'—'];});
  content.Push(rows.length?rowsTable(['Начало','Окончание','Организация / часть','Должность'],rows):paragraph('Периоды службы не указаны.'));
 }
}
