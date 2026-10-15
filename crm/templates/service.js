var doc=Api.GetDocument(); title(doc,'ПОСЛУЖНОЙ СПИСОК');
field(doc,'person','{{ФИО, звание, личный номер}}');
field(doc,'service','{{Таблица периодов службы}}');
field(doc,'signature','{{дата формирования}}');
