<?php
return [
 'HR_People' => ['Сотрудники', [
  'name'=>['ФИО','varchar'], 'personnel_number'=>['Личный номер','varchar'],
  'birth_date'=>['Дата рождения','date'], 'birth_place'=>['Место рождения','varchar'],
  'rank_name'=>['Звание','varchar'], 'rank_date'=>['Дата присвоения звания','date'],
  'position'=>['Должность','varchar'], 'department'=>['Подразделение','varchar'],
  'nationality'=>['Национальность','varchar'], 'marital_status'=>['Семейное положение','varchar'],
  'contract_start'=>['Контракт с','date'], 'contract_end'=>['Контракт по','date'],
  'manager_id'=>['Руководитель','person'], 'documents_url'=>['Сформировать документ','url'], 'description'=>['Дополнительные сведения','text'],
 ]],
 'HR_Awards'=>['Награды', ['name'=>['Название','varchar'],'person_id'=>['Сотрудник','person'],'award_date'=>['Дата награждения','date'],'order_number'=>['Номер приказа','varchar'],'description'=>['Основание','text']]],
 'HR_Service'=>['Периоды службы', ['name'=>['Организация / воинская часть','varchar'],'person_id'=>['Сотрудник','person'],'position'=>['Должность','varchar'],'start_date'=>['Начало','date'],'end_date'=>['Окончание','date'],'description'=>['Основание назначения','text']]],
 'HR_Education'=>['Образование', ['name'=>['Учебное заведение','varchar'],'person_id'=>['Сотрудник','person'],'qualification'=>['Специальность / квалификация','varchar'],'start_date'=>['Начало','date'],'end_date'=>['Окончание','date']]],
 'HR_Relatives'=>['Родственники', ['name'=>['ФИО','varchar'],'person_id'=>['Сотрудник','person'],'relation_type'=>['Степень родства','varchar'],'birth_date'=>['Дата рождения','date'],'birth_place'=>['Место рождения','varchar'],'address'=>['Адрес','text'],'occupation'=>['Место работы / занятие','varchar']]],
 'HR_Audit'=>['Аудит кадровых данных', ['name'=>['Событие','varchar'],'entity_module'=>['Модуль','varchar'],'entity_id'=>['ID записи','varchar'],'actor_name'=>['Пользователь','varchar'],'actor_id'=>['ID пользователя','varchar'],'operation'=>['Операция','varchar'],'description'=>['Изменения / снимок','text']]],
];
