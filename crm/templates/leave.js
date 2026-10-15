var doc=Api.GetDocument(); title(doc,'РАПОРТ НА ОТПУСК');
field(doc,'manager','Руководителю {{руководитель}}');
field(doc,'person','От {{звание, ФИО, должность}}');
doc.Push(paragraph('РАПОРТ',true,true));
field(doc,'leave','Прошу предоставить отпуск с {{дата}} по {{дата}}.');
field(doc,'signature','{{дата формирования}} / {{ФИО}}');
