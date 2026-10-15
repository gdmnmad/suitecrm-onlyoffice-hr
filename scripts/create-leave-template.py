"""Build the leave report reference layout with tagged block content controls."""
from pathlib import Path
from docx import Document
from docx.shared import Cm, Pt
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

doc=Document()
for st in doc.styles:
    for border in list(st.element.iter(qn('w:pBdr'))):
        border.getparent().remove(border)
section=doc.sections[0]
section.page_width=Cm(21); section.page_height=Cm(29.7)
section.top_margin=Cm(1.5); section.bottom_margin=Cm(1.5)
section.left_margin=Cm(2.5); section.right_margin=Cm(1.5)
style=doc.styles['Normal']; style.font.name='Times New Roman'; style.font.size=Pt(12)
style.paragraph_format.space_after=Pt(0); style.paragraph_format.line_spacing=1

def para(text='', align=WD_ALIGN_PARAGRAPH.JUSTIFY, indent=True, before=0):
    p=doc.add_paragraph(text); p.alignment=align
    p.paragraph_format.first_line_indent=Cm(1.25) if indent else Cm(0)
    p.paragraph_format.space_before=Pt(before)
    return p

def field(tag, label, align=WD_ALIGN_PARAGRAPH.JUSTIFY, indent=True, before=0):
    p=para('{{'+label+'}}',align,indent,before)
    sdt=OxmlElement('w:sdt'); props=OxmlElement('w:sdtPr')
    t=OxmlElement('w:tag');t.set(qn('w:val'),'leave.'+tag);props.append(t)
    alias=OxmlElement('w:alias');alias.set(qn('w:val'),label);props.append(alias)
    content=OxmlElement('w:sdtContent');p._p.addprevious(sdt);sdt.append(props);sdt.append(content);content.append(p._p)

field('recipient','Адресат',WD_ALIGN_PARAGRAPH.RIGHT,False)
p=para('Рапорт',WD_ALIGN_PARAGRAPH.CENTER,False,12);p.style=doc.styles['Title'];p.runs[0].font.name='Times New Roman';p.runs[0].font.size=Pt(14);p.runs[0].font.color.rgb=__import__('docx').shared.RGBColor(0,0,0)
field('request','Звание и ФИО, вид и причина отпуска, продолжительность и даты',before=12)
field('following','Следующий отпуск — при наличии')
field('location','Место отпуска и транспорт')
para('Инструктаж у должностных лиц по соблюдению правил и мер безопасности, поведения в отпуске, порядка постановки на воинский учёт и снятия с него получил. Знаю, что при заболевании либо наличии других уважительных причин, которые могут повлиять на моё своевременное возвращение из отпуска, я должен сообщить по месту службы причину задержки и сроки продления отпуска, оформить необходимые подтверждающие документы, а по прибытии из отпуска представить их.')
para('За время службы неуставные взаимоотношения со стороны сослуживцев ко мне не применялись, других причин, которые бы стали препятствием для моего своевременного прибытия из отпуска, в воинском коллективе нет.')
field('return','Дата, время и место возвращения')
para('Статьи Уголовного кодекса РФ, предусматривающие ответственность за воинские и общеуголовные преступления, до меня доведены. Знаю, что несвоевременное прибытие из отпуска является грубым дисциплинарным проступком. Обязуюсь возвратиться к месту службы в срок.')
field('sign_role','Должность',WD_ALIGN_PARAGRAPH.LEFT,False,12)
field('sign_rank','Звание',WD_ALIGN_PARAGRAPH.LEFT,False)
field('sign_name','ФИО и подпись',WD_ALIGN_PARAGRAPH.RIGHT,False)
field('sign_date','Дата рапорта',WD_ALIGN_PARAGRAPH.LEFT,False)
doc.add_page_break()
field('endorsement_recipient','Адресат первого ходатайства',WD_ALIGN_PARAGRAPH.RIGHT,False)
field('endorsement_text','Текст первого ходатайства',before=12)
field('endorsement_author','Первый согласующий',WD_ALIGN_PARAGRAPH.LEFT,False,12)
para('Подпись: __________________    Дата: __________________',WD_ALIGN_PARAGRAPH.LEFT,False)
field('final_recipient','Адресат второго ходатайства',WD_ALIGN_PARAGRAPH.RIGHT,False,30)
field('final_text','Текст второго ходатайства',before=12)
field('final_author','Второй согласующий',WD_ALIGN_PARAGRAPH.LEFT,False,12)
para('Подпись: __________________    Дата: __________________',WD_ALIGN_PARAGRAPH.LEFT,False)
target=Path(__file__).resolve().parents[1]/'data/templates/template-leave.docx'
doc.save(target)
print(target)
