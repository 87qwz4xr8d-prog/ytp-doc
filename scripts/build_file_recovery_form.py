#!/usr/bin/env python3
"""แบบฟอร์มบันทึกการกู้ข้อมูลไฟล์คอมพิวเตอร์ (IT-FR-001) ของบริษัท ยุธาภัคร์ จำกัด.

ช่องข้อความ วันที่ และรายการเลือกเป็น content control ของ Word
กรอบรูปเป็น picture content control: คลิกแล้วเลือกไฟล์ภาพได้จากในเอกสาร
"""

from __future__ import annotations

import io
import os
import re
import uuid
import zipfile
from xml.sax.saxutils import escape

from docx import Document
from docx.enum.section import WD_ORIENT
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_LINE_SPACING
from docx.oxml import OxmlElement
from docx.oxml.ns import nsmap, qn
from docx.shared import Cm, Mm, Pt, RGBColor, Twips
from docx.table import _Cell
from fontTools.subset import Options, Subsetter
from fontTools.ttLib import TTFont
from PIL import Image, ImageDraw, ImageFont

nsmap["w14"] = "http://schemas.microsoft.com/office/word/2010/wordml"

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "docs", "แบบฟอร์มกู้ข้อมูลไฟล์.docx")

NAVY = "0E2A47"
NAVY_MID = "1A4066"
INK = "1B242E"
MUTED = "5C6770"
BRASS = "A68546"
BRASS_TEXT = "6E5324"
BRASS_ON_NAVY = "E7D7B0"
CREAM = "F7F4EE"
LINE = "E3DCD0"
WHITE = "FFFFFF"
WARN = "8C3F2A"
WARN_BG = "FBF3EC"
ROW_ALT = "FBFAF7"

SANS = "Noto Sans"
SANS_TH = "Noto Sans Thai"
SERIF_TH = "Noto Serif Thai"
CHECK_FONT = "DejaVu Sans"

FONT_FILES = {
    (SANS, False): "/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf",
    (SANS, True): "/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf",
    (SANS_TH, False): "/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf",
    (SANS_TH, True): "/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf",
    (SERIF_TH, False): "/usr/share/fonts/truetype/noto/NotoSerifThai-Regular.ttf",
    (SERIF_TH, True): "/usr/share/fonts/truetype/noto/NotoSerifThai-Bold.ttf",
}

_SCRIPT = re.compile(r"[\u0e00-\u0e7f]+|[^\u0e00-\u0e7f]+")
_SDT_ID = 4000


def rgb(hex_color: str) -> RGBColor:
    return RGBColor(int(hex_color[0:2], 16), int(hex_color[2:4], 16), int(hex_color[4:6], 16))


def tw(length) -> int:
    return int(length.twips)


def content_width(section) -> int:
    return tw(section.page_width) - tw(section.left_margin) - tw(section.right_margin)


def next_id() -> int:
    global _SDT_ID
    _SDT_ID += 1
    return _SDT_ID


def _append(parent, tag, **attrs):
    el = OxmlElement(tag)
    for key, value in attrs.items():
        el.set(qn(key), str(value))
    parent.append(el)
    return el


def prep(p, *, before=0, after=6, line=1.08, align="left", keep_next=False, keep_lines=False):
    pf = p.paragraph_format
    pf.space_before = Pt(before)
    pf.space_after = Pt(after)
    pf.line_spacing = line
    pf.alignment = {
        "left": WD_ALIGN_PARAGRAPH.LEFT,
        "center": WD_ALIGN_PARAGRAPH.CENTER,
        "right": WD_ALIGN_PARAGRAPH.RIGHT,
        "justify": WD_ALIGN_PARAGRAPH.JUSTIFY,
    }[align]
    p_pr = p._p.get_or_add_pPr()
    _append(p_pr, "w:snapToGrid", **{"w:val": "0"})
    _append(p_pr, "w:autoSpaceDE", **{"w:val": "0"})
    _append(p_pr, "w:autoSpaceDN", **{"w:val": "0"})
    if keep_next:
        _append(p_pr, "w:keepNext", **{"w:val": "true"})
    if keep_lines:
        _append(p_pr, "w:keepLines", **{"w:val": "true"})
    return p


def para(container, *, before=0, after=6, line=1.08, align="left", keep_next=False, keep_lines=False):
    if isinstance(container, _Cell):
        existing = container.paragraphs[0]
        if len(container.paragraphs) == 1 and not existing.text and not existing.runs:
            p = existing
        else:
            p = container.add_paragraph()
    else:
        p = container.add_paragraph()
    return prep(
        p,
        before=before,
        after=after,
        line=line,
        align=align,
        keep_next=keep_next,
        keep_lines=keep_lines,
    )


def _rfonts(r_pr, latin, thai):
    r_fonts = r_pr.find(qn("w:rFonts"))
    if r_fonts is None:
        r_fonts = OxmlElement("w:rFonts")
        r_pr.insert(0, r_fonts)
    r_fonts.set(qn("w:ascii"), latin)
    r_fonts.set(qn("w:hAnsi"), latin)
    r_fonts.set(qn("w:cs"), thai)
    r_fonts.set(qn("w:eastAsia"), thai)


def run(p, text, *, size=11, bold=False, color=INK, thai=SANS_TH, latin=SANS, lang="th-TH"):
    r = p.add_run(text)
    r.bold = bold
    r.italic = False
    r.font.size = Pt(size)
    r.font.color.rgb = rgb(color)
    r_pr = r._element.get_or_add_rPr()
    _rfonts(r_pr, latin, thai)
    for tag in ("w:sz", "w:szCs"):
        node = r_pr.find(qn(tag))
        if node is None:
            node = OxmlElement(tag)
            r_pr.append(node)
        node.set(qn("w:val"), str(int(round(size * 2))))
    lang_el = r_pr.find(qn("w:lang"))
    if lang_el is None:
        lang_el = OxmlElement("w:lang")
        r_pr.append(lang_el)
    lang_el.set(qn("w:val"), lang)
    lang_el.set(qn("w:eastAsia"), "th-TH")
    lang_el.set(qn("w:bidi"), "th-TH" if lang == "th-TH" else "en-US")
    return r


def write_text(p, text, **kwargs):
    if not text:
        return
    for part in _SCRIPT.findall(text):
        lang = "th-TH" if "\u0e00" <= part[0] <= "\u0e7f" else "en-US"
        run(p, part, lang=lang, **kwargs)


def add_text(container, text, **kwargs):
    before = kwargs.pop("before", 0)
    after = kwargs.pop("after", 6)
    line = kwargs.pop("line", 1.08)
    align = kwargs.pop("align", "left")
    keep_next = kwargs.pop("keep_next", False)
    keep_lines = kwargs.pop("keep_lines", False)
    p = para(
        container,
        before=before,
        after=after,
        line=line,
        align=align,
        keep_next=keep_next,
        keep_lines=keep_lines,
    )
    write_text(p, text, **kwargs)
    return p


def spacer(container, pt):
    p = para(container, before=0, after=0, line=1.0)
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.EXACTLY
    p.paragraph_format.line_spacing = Pt(pt)
    run(p, "", size=1)
    return p


def border_paragraph(p, *, edge="bottom", color=BRASS, sz="12", space="1"):
    p_pr = p._p.get_or_add_pPr()
    p_bdr = p_pr.find(qn("w:pBdr"))
    if p_bdr is None:
        p_bdr = OxmlElement("w:pBdr")
        p_pr.append(p_bdr)
    node = OxmlElement(f"w:{edge}")
    node.set(qn("w:val"), "single")
    node.set(qn("w:sz"), sz)
    node.set(qn("w:space"), space)
    node.set(qn("w:color"), color)
    p_bdr.append(node)


def shade_cell(cell, color):
    tc_pr = cell._tc.get_or_add_tcPr()
    for old in tc_pr.findall(qn("w:shd")):
        tc_pr.remove(old)
    _append(tc_pr, "w:shd", **{"w:val": "clear", "w:color": "auto", "w:fill": color})


def cell_margins(cell, top=60, bottom=60, left=100, right=100):
    tc_pr = cell._tc.get_or_add_tcPr()
    for old in tc_pr.findall(qn("w:tcMar")):
        tc_pr.remove(old)
    mar = OxmlElement("w:tcMar")
    for name, value in (("top", top), ("left", left), ("bottom", bottom), ("right", right)):
        node = OxmlElement(f"w:{name}")
        node.set(qn("w:w"), str(value))
        node.set(qn("w:type"), "dxa")
        mar.append(node)
    tc_pr.append(mar)


def cell_borders(cell, **edges):
    tc_pr = cell._tc.get_or_add_tcPr()
    for old in tc_pr.findall(qn("w:tcBorders")):
        tc_pr.remove(old)
    borders = OxmlElement("w:tcBorders")
    for edge in ("top", "left", "bottom", "right"):
        spec = edges.get(edge, ("nil", "0", "FFFFFF"))
        node = OxmlElement(f"w:{edge}")
        val, sz, color = spec
        node.set(qn("w:val"), val)
        node.set(qn("w:sz"), str(sz))
        node.set(qn("w:space"), "0")
        node.set(qn("w:color"), color)
        borders.append(node)
    tc_pr.append(borders)


def set_row_height(row, height, rule="atLeast"):
    tr_pr = row._tr.get_or_add_trPr()
    for old in tr_pr.findall(qn("w:trHeight")):
        tr_pr.remove(old)
    _append(tr_pr, "w:trHeight", **{"w:val": str(height), "w:hRule": rule})


def cant_split(row):
    tr_pr = row._tr.get_or_add_trPr()
    _append(tr_pr, "w:cantSplit")


def header_row(row):
    tr_pr = row._tr.get_or_add_trPr()
    _append(tr_pr, "w:tblHeader")


def set_table_geometry(table, widths, indent=0):
    table.autofit = False
    table.allow_autofit = False
    table.alignment = WD_TABLE_ALIGNMENT.LEFT
    tbl = table._tbl
    tbl_pr = tbl.tblPr
    total = sum(widths)

    def upsert(tag, **attrs):
        node = tbl_pr.find(qn(tag))
        if node is None:
            node = OxmlElement(tag)
            tbl_pr.append(node)
        for key, value in attrs.items():
            node.set(qn(key), str(value))
        return node

    upsert("w:tblW", **{"w:w": total, "w:type": "dxa"})
    upsert("w:tblInd", **{"w:w": indent, "w:type": "dxa"})
    upsert("w:tblLayout", **{"w:type": "fixed"})
    look = tbl_pr.find(qn("w:tblLook"))
    if look is None:
        look = OxmlElement("w:tblLook")
        tbl_pr.append(look)
    look.set(qn("w:val"), "0000")
    look.set(qn("w:firstRow"), "0")
    look.set(qn("w:lastRow"), "0")
    look.set(qn("w:firstColumn"), "0")
    look.set(qn("w:lastColumn"), "0")
    look.set(qn("w:noHBand"), "1")
    look.set(qn("w:noVBand"), "1")

    borders = tbl_pr.find(qn("w:tblBorders"))
    if borders is not None:
        tbl_pr.remove(borders)
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        node = OxmlElement(f"w:{edge}")
        node.set(qn("w:val"), "nil")
        node.set(qn("w:sz"), "0")
        node.set(qn("w:space"), "0")
        node.set(qn("w:color"), "auto")
        borders.append(node)
    tbl_pr.append(borders)

    grid = tbl.find(qn("w:tblGrid"))
    if grid is not None:
        tbl.remove(grid)
    grid = OxmlElement("w:tblGrid")
    for width in widths:
        _append(grid, "w:gridCol", **{"w:w": width})
    tbl_pr.addnext(grid)

    for row in table.rows:
        cant_split(row)
        for idx, cell in enumerate(row.cells):
            tc_pr = cell._tc.get_or_add_tcPr()
            tc_w = tc_pr.find(qn("w:tcW"))
            if tc_w is None:
                tc_w = OxmlElement("w:tcW")
                tc_pr.append(tc_w)
            tc_w.set(qn("w:w"), str(widths[idx]))
            tc_w.set(qn("w:type"), "dxa")


def make_table(container, rows, cols, widths):
    table = container.add_table(rows=rows, cols=cols)
    table.style = "Normal Table"
    set_table_geometry(table, widths)
    return table


def v_align(cell, where="center"):
    cell.vertical_alignment = {
        "center": WD_CELL_VERTICAL_ALIGNMENT.CENTER,
        "top": WD_CELL_VERTICAL_ALIGNMENT.TOP,
        "bottom": WD_CELL_VERTICAL_ALIGNMENT.BOTTOM,
    }[where]


def lift_table(container, table):
    tbl = table._tbl
    first_p = container.paragraphs[0]._element
    parent = first_p.getparent()
    parent.remove(tbl)
    first_p.addprevious(tbl)


def field_run(p, instruction, *, size=9, bold=False, color=MUTED):
    r = p.add_run()
    r.bold = bold
    r.font.size = Pt(size)
    r.font.color.rgb = rgb(color)
    r_pr = r._element.get_or_add_rPr()
    _rfonts(r_pr, SANS, SANS_TH)
    for tag in ("w:sz", "w:szCs"):
        node = OxmlElement(tag)
        node.set(qn("w:val"), str(int(round(size * 2))))
        r_pr.append(node)
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = f" {instruction} "
    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")
    text = OxmlElement("w:t")
    text.text = "1" if instruction.strip() == "PAGE" else "4"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    r._r.append(begin)
    r._r.append(instr)
    r._r.append(separate)
    r._r.append(text)
    r._r.append(end)
    return r


def rpr_element(*, size, color, bold=False, latin=SANS, thai=SANS_TH, lang="th-TH", style=None):
    r_pr = OxmlElement("w:rPr")
    if style:
        _append(r_pr, "w:rStyle", **{"w:val": style})
    _rfonts(r_pr, latin, thai)
    if bold:
        _append(r_pr, "w:b")
        _append(r_pr, "w:bCs")
    for tag in ("w:sz", "w:szCs"):
        _append(r_pr, tag, **{"w:val": str(int(round(size * 2)))})
    _append(r_pr, "w:color", **{"w:val": color})
    lang_el = OxmlElement("w:lang")
    lang_el.set(qn("w:val"), lang)
    lang_el.set(qn("w:eastAsia"), "th-TH")
    lang_el.set(qn("w:bidi"), "th-TH" if lang == "th-TH" else "en-US")
    r_pr.append(lang_el)
    return r_pr


def append_text_runs(parent, text, *, size, color, bold=False):
    for part in _SCRIPT.findall(text):
        lang = "th-TH" if "\u0e00" <= part[0] <= "\u0e7f" else "en-US"
        r = OxmlElement("w:r")
        r.append(rpr_element(size=size, color=color, bold=bold, lang=lang))
        t = OxmlElement("w:t")
        if part[:1].isspace() or part[-1:].isspace():
            t.set(qn("xml:space"), "preserve")
        t.text = part
        r.append(t)
        parent.append(r)


def _sdt_shell(alias, tag, *, multiline=False, kind="text"):
    sdt = OxmlElement("w:sdt")
    sdt_pr = OxmlElement("w:sdtPr")
    _append(sdt_pr, "w:alias", **{"w:val": alias})
    _append(sdt_pr, "w:tag", **{"w:val": tag})
    _append(sdt_pr, "w:id", **{"w:val": str(next_id())})
    if kind == "text":
        text_el = OxmlElement("w:text")
        if multiline:
            text_el.set(qn("w:multiLine"), "true")
        sdt_pr.append(text_el)
        _append(sdt_pr, "w:showingPlcHdr")
    elif kind == "rich":
        _append(sdt_pr, "w:showingPlcHdr")
    sdt.append(sdt_pr)
    end_pr = OxmlElement("w:sdtEndPr")
    end_pr.append(rpr_element(size=11.5, color=INK))
    sdt.append(end_pr)
    content = OxmlElement("w:sdtContent")
    sdt.append(content)
    return sdt, content


def _move_into_sdt(paragraph, sdt):
    content = sdt.find(qn("w:sdtContent"))
    if content is None:
        content = OxmlElement("w:sdtContent")
        sdt.append(content)
    p_el = paragraph._p
    parent = p_el.getparent()
    parent.replace(p_el, sdt)
    content.append(p_el)
    return sdt


def add_text_control(paragraph, placeholder, tag, alias, *, size=11.5, multiline=False):
    append_text_runs(paragraph._p, placeholder, size=size, color=MUTED)
    sdt, _content = _sdt_shell(alias, tag, multiline=multiline, kind="text")
    return _move_into_sdt(paragraph, sdt)


def add_rich_control(paragraph, placeholder, tag, alias, *, size=11):
    append_text_runs(paragraph._p, placeholder, size=size, color=MUTED)
    sdt, _content = _sdt_shell(alias, tag, kind="rich")
    return _move_into_sdt(paragraph, sdt)


def add_date_control(paragraph, tag, alias, *, size=11.5):
    sdt = OxmlElement("w:sdt")
    sdt_pr = OxmlElement("w:sdtPr")
    _append(sdt_pr, "w:alias", **{"w:val": alias})
    _append(sdt_pr, "w:tag", **{"w:val": tag})
    _append(sdt_pr, "w:id", **{"w:val": str(next_id())})
    date = OxmlElement("w:date")
    _append(date, "w:dateFormat", **{"w:val": "dd/MM/yyyy"})
    _append(date, "w:lid", **{"w:val": "th-TH"})
    _append(date, "w:storeMappedDataAs", **{"w:val": "dateTime"})
    _append(date, "w:calendar", **{"w:val": "thai"})
    sdt_pr.append(date)
    _append(sdt_pr, "w:showingPlcHdr")
    sdt.append(sdt_pr)
    end_pr = OxmlElement("w:sdtEndPr")
    end_pr.append(rpr_element(size=size, color=INK, lang="en-US"))
    sdt.append(end_pr)
    append_text_runs(paragraph._p, "วว/ดด/ปปปป", size=size, color=MUTED)
    return _move_into_sdt(paragraph, sdt)


def add_dropdown(paragraph, tag, alias, items, *, size=11.5):
    sdt = OxmlElement("w:sdt")
    sdt_pr = OxmlElement("w:sdtPr")
    _append(sdt_pr, "w:alias", **{"w:val": alias})
    _append(sdt_pr, "w:tag", **{"w:val": tag})
    _append(sdt_pr, "w:id", **{"w:val": str(next_id())})
    _append(sdt_pr, "w:showingPlcHdr")
    dropdown = OxmlElement("w:dropDownList")
    for display, value in items:
        _append(dropdown, "w:listItem", **{"w:displayText": display, "w:value": value})
    sdt_pr.append(dropdown)
    sdt.append(sdt_pr)
    end_pr = OxmlElement("w:sdtEndPr")
    end_pr.append(rpr_element(size=size, color=INK))
    sdt.append(end_pr)
    append_text_runs(paragraph._p, "เลือก", size=size, color=MUTED)
    return _move_into_sdt(paragraph, sdt)


def add_checkbox(paragraph, label, tag, alias):
    sdt = OxmlElement("w:sdt")
    sdt_pr = OxmlElement("w:sdtPr")
    _append(sdt_pr, "w:alias", **{"w:val": alias})
    _append(sdt_pr, "w:tag", **{"w:val": tag})
    _append(sdt_pr, "w:id", **{"w:val": str(next_id())})
    checkbox = OxmlElement("w14:checkbox")
    _append(checkbox, "w14:checked", **{"w14:val": "0"})
    _append(checkbox, "w14:checkedState", **{"w14:val": "2612", "w14:font": CHECK_FONT})
    _append(checkbox, "w14:uncheckedState", **{"w14:val": "2610", "w14:font": CHECK_FONT})
    sdt_pr.append(checkbox)
    sdt.append(sdt_pr)
    content = OxmlElement("w:sdtContent")
    r = OxmlElement("w:r")
    r.append(rpr_element(size=13, color=NAVY, latin=CHECK_FONT, thai=CHECK_FONT, lang="en-US"))
    t = OxmlElement("w:t")
    t.text = "\u2610"
    r.append(t)
    content.append(r)
    sdt.append(content)
    paragraph._p.append(sdt)
    run(paragraph, "  ", size=10.5, color=INK)
    write_text(paragraph, label, size=10.5, color=INK)


def box_edges(color=LINE, sz="8"):
    edge = ("single", sz, color)
    return {"top": edge, "left": edge, "bottom": edge, "right": edge}


def prepare_box(cell, *, fill=CREAM, margins=(50, 60, 90, 90), border=None):
    shade_cell(cell, fill)
    cell_borders(cell, **(border or box_edges()))
    cell_margins(cell, *margins)
    v_align(cell, "top")


def columns_with_gaps(width, n, gap=130):
    if n == 1:
        return [width]
    total_gap = gap * (n - 1)
    col = (width - total_gap) // n
    widths = []
    for i in range(n):
        widths.append(col)
        if i < n - 1:
            widths.append(gap)
    widths[-1] += width - sum(widths)
    return widths


def blank_gap(cell):
    cell_borders(cell)
    cell_margins(cell, 0, 0, 0, 0)
    v_align(cell, "center")
    para(cell, before=0, after=0, line=1.0)


def field_row(doc, width, fields, *, height=760, gap=130):
    """fields: (label, kind, tag, alias, extra)

    kind is text, date, dropdown, or block.
    extra is items for a dropdown, ignored otherwise.
    """
    n = len(fields)
    widths = columns_with_gaps(width, n, gap=gap)
    table = make_table(doc, 1, len(widths), widths)
    set_row_height(table.rows[0], height, "atLeast")
    for idx, cell in enumerate(table.rows[0].cells):
        if n > 1 and idx % 2 == 1:
            blank_gap(cell)
            continue
        label, kind, tag, alias, extra = fields[idx // 2 if n > 1 else 0]
        prepare_box(cell)
        label_p = para(cell, before=0, after=0, line=1.0, keep_next=True)
        write_text(label_p, label, size=8, bold=True, color=BRASS_TEXT)
        value_p = para(cell, before=1, after=0, line=1.05)
        if kind == "text":
            add_text_control(value_p, extra or "คลิกเพื่อกรอก", tag, alias)
        elif kind == "date":
            add_date_control(value_p, tag, alias)
        elif kind == "dropdown":
            add_dropdown(value_p, tag, alias, extra)
        elif kind == "block":
            add_text_control(value_p, extra or "คลิกเพื่อกรอก", tag, alias, size=11.5, multiline=True)
        else:
            raise ValueError(kind)
    return table


def text_field(label, tag, alias, placeholder="คลิกเพื่อกรอก"):
    return (label, "text", tag, alias, placeholder)


def date_field(label, tag, alias):
    return (label, "date", tag, alias, None)


def choice_field(label, tag, alias, items):
    return (label, "dropdown", tag, alias, items)


def block_field(doc, width, label, tag, alias, placeholder, *, height=1280):
    return field_row(
        doc,
        width,
        [(label, "block", tag, alias, placeholder)],
        height=height,
    )


def section_heading(doc, number, title):
    p = para(doc, before=12, after=2, line=1.0, keep_next=True, keep_lines=True)
    run(p, number, size=12, bold=True, color=BRASS_TEXT, latin=SANS, thai=SANS_TH)
    run(p, "    ", size=12)
    run(p, title, size=14, bold=True, color=NAVY, thai=SERIF_TH, latin=SANS)
    border_paragraph(p, edge="bottom", color=BRASS, sz="10", space="1")
    return p


def hint(doc, text):
    return add_text(doc, text, size=9.5, color=MUTED, before=3, after=4, line=1.12)


def checkbox_grid(doc, width, items, *, columns=2):
    rows = (len(items) + columns - 1) // columns
    table = make_table(doc, rows, columns, [width // columns] * (columns - 1) + [width - (width // columns) * (columns - 1)])
    index = 0
    for row in table.rows:
        set_row_height(row, 360, "atLeast")
        for cell in row.cells:
            prepare_box(cell, fill=WHITE, margins=(40, 40, 80, 70), border=box_edges(LINE, "6"))
            if index < len(items):
                label, tag, alias = items[index]
                p = para(cell, before=0, after=0, line=1.0)
                add_checkbox(p, label, tag, alias)
            else:
                para(cell, before=0, after=0, line=1.0)
            index += 1
    return table


def configure_page(section):
    section.page_width = Mm(210)
    section.page_height = Mm(297)
    section.orientation = WD_ORIENT.PORTRAIT
    section.top_margin = Cm(1.65)
    section.bottom_margin = Cm(1.45)
    section.left_margin = Cm(1.45)
    section.right_margin = Cm(1.45)
    section.header_distance = Cm(0.48)
    section.footer_distance = Cm(0.38)
    section.gutter = Cm(0)
    section.different_first_page_header_footer = True


def build_header(header, width):
    header.is_linked_to_previous = False
    table = header.add_table(1, 2, width=Twips(width))
    table.style = "Normal Table"
    set_table_geometry(table, [int(width * 0.62), width - int(width * 0.62)])
    left, right = table.cell(0, 0), table.cell(0, 1)
    bottom = ("single", "10", BRASS)
    nil = ("nil", "0", WHITE)
    for cell, lines, align, margins in (
        (left, [("บริษัท ยุธาภัคร์ จำกัด", True, NAVY)], "left", (20, 40, 0, 60)),
        (right, [("แบบฟอร์มกู้ข้อมูลไฟล์  ·  IT-FR-001", True, BRASS_TEXT)], "right", (20, 40, 60, 0)),
    ):
        shade_cell(cell, WHITE)
        cell_borders(cell, top=nil, left=nil, right=nil, bottom=bottom)
        cell_margins(cell, *margins)
        v_align(cell, "bottom")
        p = para(cell, before=0, after=0, line=1.0, align=align)
        text, bold, color = lines[0]
        write_text(p, text, size=9, bold=bold, color=color)
    lift_table(header, table)
    prep(header.paragraphs[0], before=0, after=0, line=1.0)
    run(header.paragraphs[0], "", size=1)


def build_footer(footer, width):
    footer.is_linked_to_previous = False
    table = footer.add_table(1, 2, width=Twips(width))
    table.style = "Normal Table"
    set_table_geometry(table, [int(width * 0.74), width - int(width * 0.74)])
    left, right = table.cell(0, 0), table.cell(0, 1)
    top = ("single", "8", BRASS)
    nil = ("nil", "0", WHITE)
    shade_cell(left, WHITE)
    cell_borders(left, top=top, left=nil, right=nil, bottom=nil)
    cell_margins(left, 60, 10, 0, 60)
    v_align(left, "center")
    p = para(left, before=0, after=0, line=1.0)
    write_text(p, "ฝ่ายพัฒนาระบบสารสนเทศ  ·  ใช้ภายในบริษัท  ·  ไฟล์ที่กู้ได้เป็นข้อมูลลับ", size=8, color=MUTED)
    shade_cell(right, WHITE)
    cell_borders(right, top=top, left=nil, right=nil, bottom=nil)
    cell_margins(right, 60, 10, 40, 0)
    v_align(right, "center")
    p = para(right, before=0, after=0, line=1.0, align="right")
    run(p, "หน้า ", size=8, color=MUTED)
    field_run(p, "PAGE", size=8, color=NAVY, bold=True)
    run(p, " / ", size=8, color=MUTED)
    field_run(p, "NUMPAGES", size=8, color=MUTED)
    lift_table(footer, table)
    prep(footer.paragraphs[0], before=0, after=0, line=1.0)
    run(footer.paragraphs[0], "", size=1)


def empty_header(header):
    header.is_linked_to_previous = False
    prep(header.paragraphs[0], before=0, after=0, line=1.0)
    header.paragraphs[0].paragraph_format.line_spacing_rule = WD_LINE_SPACING.EXACTLY
    header.paragraphs[0].paragraph_format.line_spacing = Pt(2)
    run(header.paragraphs[0], "", size=1)


PRIORITY = [
    ("เลือก", "unset"),
    ("ปกติ", "normal"),
    ("เร่งด่วน ภายใน 1 วัน", "urgent"),
    ("วิกฤต งานหยุดอยู่", "critical"),
]
STATUS = [
    ("เลือก", "unset"),
    ("รับเรื่องแล้ว", "accepted"),
    ("กำลังกู้", "in_progress"),
    ("กู้ได้ครบ", "full"),
    ("กู้ได้บางส่วน", "partial"),
    ("กู้ไม่ได้", "failed"),
    ("ส่งต่อผู้เชี่ยวชาญ", "escalated"),
]
CHANNEL = [
    ("เลือก", "unset"),
    ("แจ้งด้วยตนเอง", "walk_in"),
    ("โทรศัพท์", "phone"),
    ("อีเมล", "email"),
    ("แอปแชทของบริษัท", "chat"),
]
DEVICE = [
    ("เลือก", "unset"),
    ("คอมพิวเตอร์ตั้งโต๊ะ", "desktop"),
    ("โน้ตบุ๊ก", "laptop"),
    ("เซิร์ฟเวอร์", "server"),
    ("ฮาร์ดดิสก์ภายนอก", "external"),
    ("แฟลชไดรฟ์", "flash"),
    ("อื่นๆ", "other"),
]
DISK_KIND = [
    ("เลือก", "unset"),
    ("HDD", "hdd"),
    ("SSD", "ssd"),
    ("NVMe", "nvme"),
    ("แฟลชไดรฟ์", "flash"),
    ("ไม่ทราบ", "unknown"),
]
DISK_HEALTH = [
    ("เลือก", "unset"),
    ("ปกติ", "ok"),
    ("มีเสียงผิดปกติ", "noise"),
    ("ระบบไม่เห็นไดรฟ์", "missing"),
    ("ตกหรือกระแทก", "shock"),
    ("ไม่ทราบ", "unknown"),
]
BACKUP = [
    ("เลือก", "unset"),
    ("มีสำเนาล่าสุด", "yes"),
    ("ไม่มีสำเนา", "no"),
    ("ไม่ทราบ", "unknown"),
]
IMPORTANCE = [
    ("เลือก", "unset"),
    ("สูงมาก", "critical"),
    ("สูง", "high"),
    ("ปานกลาง", "medium"),
    ("ต่ำ", "low"),
]
FILE_RESULT = [
    ("เลือก", "unset"),
    ("รอดำเนินการ", "pending"),
    ("กู้ได้", "recovered"),
    ("ได้บางส่วน", "partial"),
    ("กู้ไม่ได้", "failed"),
    ("ไม่พบไฟล์นี้", "not_found"),
]
DELIVERY = [
    ("เลือก", "unset"),
    ("แฟลชไดรฟ์", "flash"),
    ("โฟลเดอร์ส่วนกลาง", "share"),
    ("อีเมล", "email"),
    ("ลิงก์ภายใน", "link"),
    ("อื่นๆ", "other"),
]
OUTCOME = [
    ("เลือก", "unset"),
    ("กู้ได้ครบทุกรายการ", "full"),
    ("กู้ได้บางส่วน", "partial"),
    ("กู้ไม่ได้", "failed"),
    ("ส่งต่อผู้เชี่ยวชาญภายนอก", "lab"),
]


def build_title(doc, width):
    left_w = int(width * 0.74)
    table = make_table(doc, 1, 2, [left_w, width - left_w])
    set_row_height(table.rows[0], 980, "atLeast")
    left, right = table.cell(0, 0), table.cell(0, 1)
    nil = ("nil", "0", NAVY)
    for cell in (left, right):
        shade_cell(cell, NAVY)
        cell_borders(cell, top=nil, left=nil, bottom=nil, right=nil)
        v_align(cell, "center")
    cell_margins(left, 120, 120, 180, 80)
    cell_margins(right, 120, 110, 40, 180)
    p = para(left, before=0, after=0, line=1.0)
    write_text(p, "บริษัท ยุธาภัคร์ จำกัด", size=9, bold=True, color=BRASS_ON_NAVY)
    p = para(left, before=1, after=0, line=1.0)
    write_text(p, "ฝ่ายพัฒนาระบบสารสนเทศ", size=9, color=BRASS_ON_NAVY)
    p = para(left, before=4, after=0, line=1.0)
    write_text(p, "แบบฟอร์มบันทึกการกู้ข้อมูลไฟล์", size=20, bold=True, color=WHITE, thai=SERIF_TH, latin=SANS)
    p = para(left, before=1, after=0, line=1.0)
    write_text(p, "คอมพิวเตอร์  ฮาร์ดดิสก์  แฟลชไดรฟ์ และไฟล์ที่เปิดไม่ได้", size=10, color=BRASS_ON_NAVY)

    p = para(right, before=0, after=0, line=1.0, align="right")
    write_text(p, "IT-FR-001", size=16, bold=True, color=BRASS_ON_NAVY, latin=SANS, thai=SANS)
    p = para(right, before=2, after=0, line=1.0, align="right")
    write_text(p, "ใช้ภายในบริษัท", size=9, color=WHITE)
    p = para(right, before=1, after=0, line=1.0, align="right")
    write_text(p, "พฤศจิกายน 2569", size=9, color=BRASS_ON_NAVY)

    rule = make_table(doc, 1, 1, [width])
    set_row_height(rule.rows[0], 90, "exact")
    cell = rule.cell(0, 0)
    shade_cell(cell, BRASS)
    cell_borders(cell)
    cell_margins(cell, 0, 0, 0, 0)
    para(cell, before=0, after=0, line=1.0)
    spacer(doc, 8)


def build_howto(doc, width):
    steps = [
        ("01", "กรอกข้อความ", "คลิกช่องแล้วพิมพ์"),
        ("02", "เลือกรายการ", "คลิกช่องแล้วเลือกคำตอบ"),
        ("03", "ใส่รูปภาพ", "คลิกกรอบรูป แล้วเลือกไฟล์"),
    ]
    widths = columns_with_gaps(width, 3, gap=120)
    table = make_table(doc, 1, len(widths), widths)
    set_row_height(table.rows[0], 620, "atLeast")
    step_i = 0
    for idx, cell in enumerate(table.rows[0].cells):
        if idx % 2 == 1:
            blank_gap(cell)
            continue
        number, title, detail = steps[step_i]
        step_i += 1
        prepare_box(
            cell,
            fill=CREAM,
            margins=(70, 70, 120, 100),
            border={
                "top": ("nil", "0", WHITE),
                "left": ("single", "16", BRASS),
                "bottom": ("nil", "0", WHITE),
                "right": ("nil", "0", WHITE),
            },
        )
        p = para(cell, before=0, after=0, line=1.0)
        write_text(p, number, size=9, bold=True, color=BRASS_TEXT)
        p = para(cell, before=0, after=0, line=1.0)
        write_text(p, title, size=11, bold=True, color=NAVY)
        p = para(cell, before=0, after=0, line=1.0)
        write_text(p, detail, size=9, color=MUTED)
    spacer(doc, 6)
    add_text(
        doc,
        "ผู้แจ้งกรอกส่วนที่ 1 ถึง 5 ให้ครบ โดยเฉพาะอาการและรายการไฟล์  เจ้าหน้าที่สารสนเทศกรอกส่วนที่ 6 ถึง 9 และใส่รูปหลักฐาน",
        size=9.5,
        color=INK,
        before=2,
        after=3,
        line=1.12,
    )


def build_warning(doc, width):
    table = make_table(doc, 1, 1, [width])
    cell = table.cell(0, 0)
    prepare_box(
        cell,
        fill=WARN_BG,
        margins=(70, 70, 140, 120),
        border={
            "top": ("nil", "0", WHITE),
            "left": ("single", "16", WARN),
            "bottom": ("nil", "0", WHITE),
            "right": ("nil", "0", WHITE),
        },
    )
    p = para(cell, before=0, after=1, line=1.0)
    write_text(p, "ก่อนลงมือกู้", size=8.5, bold=True, color=WARN)
    p = para(cell, before=0, after=0, line=1.12)
    write_text(
        p,
        "ห้ามบันทึกไฟล์ที่กู้ได้กลับลงดิสก์ต้นทาง และห้ามติดตั้งโปรแกรมบนดิสก์นั้นก่อนทำสำเนา การเขียนทับอาจทำให้ไฟล์ที่หายกู้คืนไม่ได้",
        size=10,
        color=INK,
    )


def build_people(doc, width):
    gap = 140
    col = (width - gap) // 2
    table = make_table(doc, 1, 3, [col, gap, width - col - gap])
    groups = [
        (
            "ผู้แจ้งเหตุ",
            [
                text_field("ชื่อ-สกุล", "requester_name", "ชื่อผู้แจ้ง"),
                text_field("แผนก", "requester_dept", "แผนกผู้แจ้ง"),
                text_field("ตำแหน่ง", "requester_title", "ตำแหน่งผู้แจ้ง"),
                text_field("โทรศัพท์", "requester_phone", "โทรศัพท์ผู้แจ้ง", "เช่น 0812345678"),
                text_field("อีเมล", "requester_email", "อีเมลผู้แจ้ง", "name@company.co.th"),
            ],
        ),
        (
            "ผู้รับเรื่อง ฝ่ายสารสนเทศ",
            [
                text_field("ชื่อ-สกุล", "operator_name", "ชื่อผู้รับเรื่อง"),
                text_field("โทรศัพท์", "operator_phone", "โทรศัพท์ผู้รับเรื่อง", "เช่น 0812345678"),
                date_field("วันที่เริ่มดำเนินการ", "work_started", "วันที่เริ่มดำเนินการ"),
                text_field("เวลาที่เริ่ม", "work_started_time", "เวลาที่เริ่ม", "เช่น 09:30"),
            ],
        ),
    ]
    blank_gap(table.cell(0, 1))
    for cell, (title, fields) in zip((table.cell(0, 0), table.cell(0, 2)), groups):
        prepare_box(cell, fill=WHITE, margins=(80, 90, 120, 120))
        p = para(cell, before=0, after=4, line=1.0)
        write_text(p, title, size=12, bold=True, color=NAVY, thai=SERIF_TH, latin=SANS)
        border_paragraph(p, edge="bottom", color=BRASS, sz="8", space="1")
        for label, kind, tag, alias, extra in fields:
            label_p = para(cell, before=6, after=0, line=1.0, keep_next=True)
            write_text(label_p, label, size=8, bold=True, color=BRASS_TEXT)
            value_p = para(cell, before=0, after=1, line=1.0)
            border_paragraph(value_p, edge="bottom", color=LINE, sz="8", space="1")
            if kind == "text":
                add_text_control(value_p, extra or "คลิกเพื่อกรอก", tag, alias)
            else:
                add_date_control(value_p, tag, alias)


def build_checks(doc, width, items):
    spacer(doc, 4)
    checkbox_grid(doc, width, items)


def build_file_table(doc, width):
    no, size, imp, res = 700, 1350, 1450, 1550
    rest = width - (no + size + imp + res)
    name = int(rest * 0.46)
    path = rest - name
    widths = [no, name, path, size, imp, res]
    headers = ["ลำดับ", "ชื่อไฟล์หรือโฟลเดอร์", "ที่อยู่เดิมในเครื่อง", "ขนาดโดยประมาณ", "ความสำคัญ", "ผลการกู้"]
    table = make_table(doc, 7, 6, widths)
    header_row(table.rows[0])
    set_row_height(table.rows[0], 520, "atLeast")
    for idx, cell in enumerate(table.rows[0].cells):
        shade_cell(cell, NAVY)
        cell_borders(
            cell,
            top=("single", "4", NAVY),
            left=("single", "4", NAVY_MID),
            bottom=("single", "8", BRASS),
            right=("single", "4", NAVY_MID),
        )
        cell_margins(cell, 50, 50, 60, 60)
        v_align(cell, "center")
        align = "left" if idx in (1, 2) else "center"
        p = para(cell, before=0, after=0, line=1.0, align=align)
        write_text(p, headers[idx], size=8, bold=True, color=WHITE)
    for row_no in range(1, 7):
        row = table.rows[row_no]
        set_row_height(row, 460, "atLeast")
        fill = WHITE if row_no % 2 else ROW_ALT
        values = [
            ("plain", str(row_no)),
            ("text", f"file{row_no}_name", "ชื่อไฟล์", "ชื่อไฟล์"),
            ("text", f"file{row_no}_path", "ที่อยู่เดิม", "เช่น D:\\งาน\\ไฟล์.xlsx"),
            ("text", f"file{row_no}_size", "ขนาดไฟล์", "เช่น 25 MB"),
            ("drop", f"file{row_no}_importance", "ความสำคัญ", IMPORTANCE),
            ("drop", f"file{row_no}_result", "ผลการกู้", FILE_RESULT),
        ]
        for idx, cell in enumerate(row.cells):
            shade_cell(cell, fill)
            cell_borders(cell, **box_edges(LINE, "4"))
            cell_margins(cell, 40, 40, 60, 60)
            v_align(cell, "center")
            align = "left" if idx in (1, 2) else "center"
            p = para(cell, before=0, after=0, line=1.0, align=align)
            kind = values[idx][0]
            if kind == "plain":
                write_text(p, values[idx][1], size=10, bold=True, color=NAVY)
            elif kind == "text":
                _, tag, alias, placeholder = values[idx]
                add_text_control(p, placeholder, tag, alias, size=9.5)
            else:
                _, tag, alias, items = values[idx]
                add_dropdown(p, tag, alias, items, size=9.5)
    return table


def make_placeholder(title: str, subtitle: str) -> bytes:
    width, height = 1400, 788
    image = Image.new("RGB", (width, height), (247, 244, 238))
    draw = ImageDraw.Draw(image)
    navy = (14, 42, 71)
    brass = (166, 133, 70)
    muted = (92, 103, 112)
    for i in range(5):
        draw.rectangle([i, i, width - 1 - i, height - 1 - i], outline=brass)
    draw.rectangle([26, 26, width - 27, height - 27], outline=(214, 201, 176), width=2)
    arm = 78
    margin = 48
    for x, y, dx, dy in (
        (margin, margin, arm, arm),
        (width - margin, margin, -arm, arm),
        (margin, height - margin, arm, -arm),
        (width - margin, height - margin, -arm, -arm),
    ):
        draw.line([(x, y), (x + dx, y)], fill=navy, width=5)
        draw.line([(x, y), (x, y + dy)], fill=navy, width=5)

    icon = (width // 2, 250)
    draw.rounded_rectangle([icon[0] - 78, icon[1] - 58, icon[0] + 78, icon[1] + 58], radius=12, outline=brass, width=5)
    draw.ellipse([icon[0] - 28, icon[1] - 28, icon[0] + 28, icon[1] + 28], outline=brass, width=5)
    draw.ellipse([icon[0] + 40, icon[1] - 40, icon[0] + 56, icon[1] - 24], fill=brass)
    draw.polygon([(icon[0] - 62, icon[1] + 46), (icon[0] - 20, icon[1] + 2), (icon[0] + 8, icon[1] + 46)], fill=(232, 220, 196))

    bold = ImageFont.truetype(FONT_FILES[(SANS_TH, True)], 52)
    regular = ImageFont.truetype(FONT_FILES[(SANS_TH, False)], 30)
    small = ImageFont.truetype(FONT_FILES[(SANS_TH, False)], 24)
    latin = ImageFont.truetype(FONT_FILES[(SANS, False)], 24)
    draw.text((width / 2, 430), title, font=bold, fill=navy, anchor="mm")
    draw.text((width / 2, 500), "คลิกที่รูปนี้ แล้วเลือกไฟล์ภาพ", font=regular, fill=muted, anchor="mm")
    draw.text((width / 2, 548), subtitle, font=small, fill=brass, anchor="mm")
    draw.text((width / 2, 690), "PNG    ·    JPG    ·    JPEG", font=latin, fill=muted, anchor="mm")
    buf = io.BytesIO()
    image.save(buf, format="PNG", optimize=True)
    return buf.getvalue()


def add_picture_control(cell, image_bytes, display_width, *, tag, alias):
    p = para(cell, before=2, after=2, line=1.0, align="center")
    pic_run = p.add_run()
    pic_run.add_picture(io.BytesIO(image_bytes), width=display_width)
    r_pr = pic_run._element.get_or_add_rPr()
    _append(r_pr, "w:noProof")
    drawing = pic_run._element.find(qn("w:drawing"))
    inline = drawing.find(qn("wp:inline"))
    doc_pr = inline.find(qn("wp:docPr"))
    doc_pr.set("name", alias)
    doc_pr.set("descr", "คลิกเพื่อใส่รูปภาพ " + alias)
    sdt = OxmlElement("w:sdt")
    sdt_pr = OxmlElement("w:sdtPr")
    _append(sdt_pr, "w:alias", **{"w:val": alias})
    _append(sdt_pr, "w:tag", **{"w:val": tag})
    _append(sdt_pr, "w:id", **{"w:val": str(next_id())})
    _append(sdt_pr, "w:picture")
    sdt.append(sdt_pr)
    content = OxmlElement("w:sdtContent")
    sdt.append(content)
    return _move_into_sdt(p, sdt)


def build_photos(doc, width):
    slots = [
        ("รูปที่ 1", "หน้าจออาการหรือข้อความแจ้งเตือน", "ภาพข้อความบนหน้าจอขณะเกิดปัญหา", "photo_error", "รูปหน้าจออาการ"),
        ("รูปที่ 2", "ตำแหน่งไฟล์หรือโฟลเดอร์เดิม", "ภาพหน้าต่างที่เคยเปิดไฟล์นี้", "photo_location", "รูปตำแหน่งไฟล์"),
        ("รูปที่ 3", "ภาพอุปกรณ์หรือดิสก์", "ภาพเครื่อง คอมพิวเตอร์ หรือฮาร์ดดิสก์", "photo_device", "รูปอุปกรณ์"),
        ("รูปที่ 4", "ภาพผลหลังกู้ไฟล์", "ภาพไฟล์ที่ได้คืนหรือโฟลเดอร์ปลายทาง", "photo_result", "รูปผลหลังกู้"),
    ]
    images = [make_placeholder(title, subtitle) for _num, title, subtitle, _tag, _alias in slots]
    gap = 140
    col = (width - gap) // 2
    picture_width = Cm(col / 1440 * 2.54 - 0.55)
    for row_i in range(2):
        if row_i:
            spacer(doc, 6)
        table = make_table(doc, 1, 3, [col, gap, width - col - gap])
        blank_gap(table.cell(0, 1))
        for side, slot, blob in (
            (0, slots[row_i * 2], images[row_i * 2]),
            (2, slots[row_i * 2 + 1], images[row_i * 2 + 1]),
        ):
            number, title, subtitle, tag, alias = slot
            cell = table.cell(0, side)
            prepare_box(cell, fill=CREAM, margins=(70, 80, 80, 80))
            p = para(cell, before=0, after=0, line=1.0, keep_next=True)
            write_text(p, number, size=8, bold=True, color=BRASS_TEXT)
            run(p, "    ", size=8)
            write_text(p, title, size=11, bold=True, color=NAVY)
            add_picture_control(cell, blob, picture_width, tag=tag, alias=alias)
            cap = para(cell, before=1, after=0, line=1.0, keep_next=True)
            write_text(cap, "คำอธิบายรูป", size=8, bold=True, color=BRASS_TEXT)
            value = para(cell, before=0, after=0, line=1.0)
            add_text_control(value, subtitle, tag + "_caption", alias + " คำอธิบาย", size=10)

    spacer(doc, 8)
    table = make_table(doc, 1, 1, [width])
    set_row_height(table.rows[0], 1500, "atLeast")
    cell = table.cell(0, 0)
    prepare_box(cell, fill=WHITE, margins=(80, 80, 120, 120))
    p = para(cell, before=0, after=1, line=1.0, keep_next=True)
    write_text(p, "รูปเพิ่มเติม", size=11, bold=True, color=NAVY, thai=SERIF_TH, latin=SANS)
    p = para(cell, before=0, after=2, line=1.05)
    write_text(
        p,
        "คลิกในกรอบนี้ แล้วใช้เมนู แทรก > รูปภาพ เพื่อวางได้มากกว่าหนึ่งรูป วางภาพจากคลิปบอร์ดได้เช่นกัน",
        size=9,
        color=MUTED,
    )
    value = para(cell, before=2, after=0, line=1.08)
    add_rich_control(
        value,
        "คลิกที่นี่ แล้วแทรกรูปเพิ่ม หรือพิมพ์หมายเหตุประกอบรูป",
        "photo_extra",
        "รูปเพิ่มเติม",
        size=11,
    )


def build_signatures(doc, width):
    roles = [
        ("ผู้แจ้งเหตุ", "sign_requester"),
        ("ผู้กู้ข้อมูล", "sign_operator"),
        ("หัวหน้าฝ่ายสารสนเทศ", "sign_lead"),
    ]
    widths = columns_with_gaps(width, 3, gap=120)
    table = make_table(doc, 1, len(widths), widths)
    set_row_height(table.rows[0], 2100, "atLeast")
    role_i = 0
    for idx, cell in enumerate(table.rows[0].cells):
        if idx % 2 == 1:
            blank_gap(cell)
            continue
        role, prefix = roles[role_i]
        role_i += 1
        prepare_box(cell, fill=WHITE, margins=(80, 80, 110, 110))
        p = para(cell, before=0, after=3, line=1.0)
        write_text(p, role, size=11, bold=True, color=NAVY, thai=SERIF_TH, latin=SANS)
        border_paragraph(p, edge="bottom", color=BRASS, sz="8", space="1")
        label = para(cell, before=6, after=0, line=1.0, keep_next=True)
        write_text(label, "ชื่อ-สกุล", size=8, bold=True, color=BRASS_TEXT)
        value = para(cell, before=0, after=1, line=1.0)
        border_paragraph(value, edge="bottom", color=LINE, sz="6", space="1")
        add_text_control(value, "คลิกเพื่อกรอก", prefix + "_name", role + " ชื่อ")
        label = para(cell, before=5, after=0, line=1.0, keep_next=True)
        write_text(label, "วันที่", size=8, bold=True, color=BRASS_TEXT)
        value = para(cell, before=0, after=1, line=1.0)
        border_paragraph(value, edge="bottom", color=LINE, sz="6", space="1")
        add_date_control(value, prefix + "_date", role + " วันที่")
        label = para(cell, before=8, after=0, line=1.0)
        write_text(label, "ลายมือชื่อ", size=8, bold=True, color=BRASS_TEXT)
        sign = para(cell, before=32, after=2, line=1.0)
        border_paragraph(sign, edge="bottom", color=NAVY, sz="8", space="1")
        run(sign, " ", size=11)


def page_break(doc):
    p = para(doc, before=0, after=0, line=1.0)
    run(p, "", size=2)
    p.runs[0].add_break(WD_BREAK.PAGE)


def apply_doc_defaults(doc: Document) -> None:
    styles = doc.styles.element
    existing = styles.find(qn("w:docDefaults"))
    if existing is not None:
        styles.remove(existing)
    defaults = OxmlElement("w:docDefaults")
    r_pr_default = OxmlElement("w:rPrDefault")
    r_pr = OxmlElement("w:rPr")
    r_fonts = OxmlElement("w:rFonts")
    r_fonts.set(qn("w:ascii"), SANS)
    r_fonts.set(qn("w:hAnsi"), SANS)
    r_fonts.set(qn("w:cs"), SANS_TH)
    r_fonts.set(qn("w:eastAsia"), SANS_TH)
    r_pr.append(r_fonts)
    for tag, val in (("w:sz", "22"), ("w:szCs", "22")):
        node = OxmlElement(tag)
        node.set(qn("w:val"), val)
        r_pr.append(node)
    lang = OxmlElement("w:lang")
    lang.set(qn("w:val"), "th-TH")
    lang.set(qn("w:eastAsia"), "th-TH")
    lang.set(qn("w:bidi"), "th-TH")
    r_pr.append(lang)
    r_pr_default.append(r_pr)
    defaults.append(r_pr_default)
    p_pr_default = OxmlElement("w:pPrDefault")
    p_pr = OxmlElement("w:pPr")
    spacing = OxmlElement("w:spacing")
    spacing.set(qn("w:before"), "0")
    spacing.set(qn("w:after"), "0")
    spacing.set(qn("w:line"), "240")
    spacing.set(qn("w:lineRule"), "auto")
    p_pr.append(spacing)
    p_pr_default.append(p_pr)
    defaults.append(p_pr_default)
    styles.insert(0, defaults)

    normal = doc.styles["Normal"]
    normal.font.name = SANS
    normal.font.size = Pt(11)
    normal.font.color.rgb = rgb(INK)
    r_pr = normal.element.get_or_add_rPr()
    _rfonts(r_pr, SANS, SANS_TH)


def subset_checkbox_font() -> bytes:
    font = TTFont("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf")
    options = Options()
    options.recommended_glyphs = True
    options.name_IDs = ["*"]
    options.layout_features = ["*"]
    subsetter = Subsetter(options=options)
    subsetter.populate(unicodes=[0x2610, 0x2612])
    subsetter.subset(font)
    cmap = font.getBestCmap()
    if 0x2610 not in cmap or 0x2612 not in cmap:
        raise RuntimeError("checkbox glyphs missing after subset")
    buf = io.BytesIO()
    font.save(buf)
    return buf.getvalue()


def _guid_key(guid: str) -> bytes:
    parts = guid.strip("{}").split("-")
    key = bytearray()
    key += bytes.fromhex(parts[0])[::-1]
    key += bytes.fromhex(parts[1])[::-1]
    key += bytes.fromhex(parts[2])[::-1]
    key += bytes.fromhex(parts[3])
    key += bytes.fromhex(parts[4])
    if len(key) != 16:
        raise RuntimeError("bad font GUID key")
    return bytes(key)


def obfuscate(data: bytes, guid: str) -> bytes:
    key = _guid_key(guid)
    buf = bytearray(data)
    for i in range(min(32, len(buf))):
        buf[i] ^= key[i % 16]
    return bytes(buf)


def patch_settings(data: bytes) -> bytes:
    text = data.decode("utf-8")
    text = text.replace("<w:useFELayout/>", "")
    text = text.replace(
        '<w:themeFontLang w:val="en-US" w:eastAsia="ja-JP"/>',
        '<w:themeFontLang w:val="th-TH" w:eastAsia="th-TH" w:bidi="th-TH"/>',
    )
    text = text.replace('<w:zoom w:val="bestFit"/>', '<w:zoom w:percent="110"/>')
    if "<w:embedTrueTypeFonts" not in text:
        text = text.replace(
            "<w:compat>",
            '<w:embedTrueTypeFonts/><w:embedSystemFonts/><w:autoHyphenation w:val="false"/>'
            '<w:updateFields w:val="true"/><w:compat>',
        )
    return text.encode("utf-8")


def patch_content_types(data: bytes) -> bytes:
    text = data.decode("utf-8")
    if 'Extension="odttf"' not in text:
        insert = '<Default Extension="odttf" ContentType="application/vnd.openxmlformats-officedocument.obfuscatedFont"/>'
        start = text.find("<Types")
        idx = text.find(">", start)
        text = text[: idx + 1] + insert + text[idx + 1 :]
    return text.encode("utf-8")


def embed_fonts(path: str, checkbox_font: bytes) -> None:
    families = {}
    rels = []
    blobs = {}
    rid = 1
    payloads = [((family, bold), open(font_path, "rb").read()) for (family, bold), font_path in FONT_FILES.items()]
    payloads.append(((CHECK_FONT, False), checkbox_font))
    for (family, bold), raw in payloads:
        guid = "{" + str(uuid.uuid4()).upper() + "}"
        data = obfuscate(raw, guid)
        name = f"word/fonts/font{rid}.odttf"
        blobs[name] = data
        rel_id = f"rIdFont{rid}"
        rels.append((rel_id, name))
        families.setdefault(family, {})["bold" if bold else "regular"] = (rel_id, guid)
        magic = bytearray(data[:4])
        key = _guid_key(guid)
        for i in range(4):
            magic[i] ^= key[i % 16]
        if bytes(magic) not in (b"\x00\x01\x00\x00", b"OTTO", b"true", b"typ1"):
            raise RuntimeError(f"font obfuscation failed for {family}")
        rid += 1

    font_xml = [
        "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>",
        '<w:fonts xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">',
    ]
    for family, faces in families.items():
        font_xml.append(f'  <w:font w:name="{escape(family)}">')
        font_xml.append('    <w:charset w:val="00"/>')
        font_xml.append('    <w:family w:val="swiss"/>')
        font_xml.append('    <w:pitch w:val="variable"/>')
        if "regular" in faces:
            rel_id, guid = faces["regular"]
            font_xml.append(f'    <w:embedRegular r:id="{rel_id}" w:fontKey="{guid}"/>')
        if "bold" in faces:
            rel_id, guid = faces["bold"]
            font_xml.append(f'    <w:embedBold r:id="{rel_id}" w:fontKey="{guid}"/>')
        font_xml.append("  </w:font>")
    font_xml.append("</w:fonts>")

    rels_xml = [
        "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>",
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">',
    ]
    for rel_id, name in rels:
        target = name.split("/", 1)[1]
        rels_xml.append(
            f'  <Relationship Id="{rel_id}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/font" Target="{target}"/>'
        )
    rels_xml.append("</Relationships>")

    tmp = path + ".tmp"
    with zipfile.ZipFile(path, "r") as zin, zipfile.ZipFile(tmp, "w", compression=zipfile.ZIP_DEFLATED) as zout:
        for item in zin.infolist():
            data = zin.read(item.filename)
            if item.filename == "word/fontTable.xml":
                data = "\n".join(font_xml).encode("utf-8")
            elif item.filename == "word/settings.xml":
                data = patch_settings(data)
            elif item.filename == "[Content_Types].xml":
                data = patch_content_types(data)
            elif item.filename == "word/_rels/fontTable.xml.rels":
                continue
            zout.writestr(item.filename, data)
        zout.writestr("word/_rels/fontTable.xml.rels", "\n".join(rels_xml).encode("utf-8"))
        for name, blob in blobs.items():
            zout.writestr(name, blob)
    os.replace(tmp, path)


def validate(path: str) -> None:
    from lxml import etree

    with zipfile.ZipFile(path) as zf:
        xml = zf.read("word/document.xml")
        media = [name for name in zf.namelist() if name.startswith("word/media/")]
    tree = etree.fromstring(xml)
    ns = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
    pictures = tree.xpath("//w:picture", namespaces=ns)
    drawings = tree.xpath("//w:drawing", namespaces=ns)
    tags = [el.get(qn("w:val")) for el in tree.xpath("//w:tag", namespaces=ns)]
    if len(pictures) != 4 or len(drawings) != 4 or len(media) != 4:
        raise SystemExit(f"picture controls mismatch pictures={len(pictures)} drawings={len(drawings)} media={len(media)}")
    required = {"requester_name", "photo_error", "photo_extra", "file1_name", "sign_operator_name"}
    missing = required - set(tags)
    if missing:
        raise SystemExit(f"missing controls: {missing}")
    texts = [t.text or "" for t in tree.xpath("//w:t", namespaces=ns)]
    blob = "\n".join(texts)
    needed = {}
    for ch in set(blob):
        if ch.isspace() or ch in "\u2610\u2612":
            continue
        needed.setdefault("thai" if "\u0e00" <= ch <= "\u0e7f" else "latin", set()).add(ch)
    thai = TTFont(FONT_FILES[(SANS_TH, False)]).getBestCmap()
    thai_bold = TTFont(FONT_FILES[(SANS_TH, True)]).getBestCmap()
    latin = TTFont(FONT_FILES[(SANS, False)]).getBestCmap()
    serif = TTFont(FONT_FILES[(SERIF_TH, False)]).getBestCmap()
    missing_th = sorted(ch for ch in needed.get("thai", []) if ord(ch) not in thai or ord(ch) not in thai_bold or ord(ch) not in serif)
    missing_lat = sorted(ch for ch in needed.get("latin", []) if ord(ch) not in latin)
    if missing_th or missing_lat:
        raise SystemExit(f"Missing glyphs Thai={missing_th} Latin={''.join(missing_lat)}")


def build():
    doc = Document()
    apply_doc_defaults(doc)
    core = doc.core_properties
    core.title = "แบบฟอร์มบันทึกการกู้ข้อมูลไฟล์คอมพิวเตอร์"
    core.subject = "IT-FR-001 บริษัท ยุธาภัคร์ จำกัด"
    core.category = "แบบฟอร์มภายใน"
    core.keywords = "กู้ข้อมูล; ไฟล์; IT-FR-001"
    core.language = "th-TH"
    core.creator = "ฝ่ายพัฒนาระบบสารสนเทศ บริษัท ยุธาภัคร์ จำกัด"
    core.author = "ฝ่ายพัฒนาระบบสารสนเทศ บริษัท ยุธาภัคร์ จำกัด"
    core.last_modified_by = "ฝ่ายพัฒนาระบบสารสนเทศ บริษัท ยุธาภัคร์ จำกัด"

    section = doc.sections[0]
    configure_page(section)
    width = content_width(section)
    empty_header(section.first_page_header)
    build_header(section.header, width)
    build_footer(section.first_page_footer, width)
    build_footer(section.footer, width)

    build_title(doc, width)
    build_howto(doc, width)
    build_warning(doc, width)

    section_heading(doc, "01", "ข้อมูลแบบฟอร์ม")
    field_row(
        doc,
        width,
        [
            text_field("เลขที่งาน", "job_no", "เลขที่งาน", "เช่น FR-2569-001"),
            date_field("วันที่แจ้ง", "report_date", "วันที่แจ้ง"),
            date_field("วันที่รับเรื่อง", "received_date", "วันที่รับเรื่อง"),
        ],
    )
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            choice_field("ความเร่งด่วน", "priority", "ความเร่งด่วน", PRIORITY),
            choice_field("สถานะงาน", "status", "สถานะงาน", STATUS),
            choice_field("ช่องทางที่แจ้ง", "channel", "ช่องทางที่แจ้ง", CHANNEL),
        ],
    )

    section_heading(doc, "02", "ผู้แจ้งและผู้รับเรื่อง")
    hint(doc, "ผู้แจ้งกรอกฝั่งซ้าย เจ้าหน้าที่ผู้รับเรื่องกรอกฝั่งขวา")
    build_people(doc, width)

    section_heading(doc, "03", "เครื่องและอุปกรณ์จัดเก็บ")
    field_row(
        doc,
        width,
        [
            text_field("ชื่อเครื่อง", "computer_name", "ชื่อเครื่อง", "เช่น SALE-PC01"),
            text_field("หมายเลขครุภัณฑ์", "asset_no", "หมายเลขครุภัณฑ์"),
            text_field("หมายเลขประจำเครื่อง", "serial_no", "หมายเลขประจำเครื่อง"),
        ],
    )
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            choice_field("ประเภทอุปกรณ์", "device_type", "ประเภทอุปกรณ์", DEVICE),
            text_field("ระบบปฏิบัติการ", "os_name", "ระบบปฏิบัติการ", "เช่น Windows 11"),
            text_field("ผู้ใช้งานเครื่อง", "device_user", "ผู้ใช้งานเครื่อง"),
        ],
    )
    spacer(doc, 5)
    field_row(doc, width, [text_field("สถานที่ตั้งเครื่อง", "location", "สถานที่ตั้งเครื่อง", "เช่น ชั้น 2 แผนกบัญชี")])
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            choice_field("ชนิดดิสก์", "disk_kind", "ชนิดดิสก์", DISK_KIND),
            text_field("ความจุ", "disk_size", "ความจุ", "เช่น 1 TB"),
            text_field("ยี่ห้อหรือรุ่น", "disk_model", "รุ่นดิสก์"),
            choice_field("สุขภาพดิสก์", "disk_health", "สุขภาพดิสก์", DISK_HEALTH),
        ],
        height=820,
    )
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            choice_field("สำเนาข้อมูลล่าสุด", "backup_status", "สำเนาข้อมูลล่าสุด", BACKUP),
            date_field("วันที่สำรองล่าสุด", "backup_date", "วันที่สำรองล่าสุด"),
            text_field("ที่เก็บสำเนา", "backup_location", "ที่เก็บสำเนา", "เช่น ฮาร์ดดิสก์นอก หรือโฟลเดอร์ส่วนกลาง"),
        ],
    )

    section_heading(doc, "04", "เหตุการณ์ที่ทำให้ไฟล์สูญหาย")
    hint(doc, "เลือกได้มากกว่าหนึ่งอาการ แล้วเล่ารายละเอียดในช่องด้านล่าง")
    field_row(
        doc,
        width,
        [
            date_field("วันที่พบปัญหา", "incident_date", "วันที่พบปัญหา"),
            text_field("เวลาที่พบ", "incident_time", "เวลาที่พบ", "เช่น 14:20"),
            date_field("วันที่ไฟล์ยังใช้ได้ล่าสุด", "last_good_date", "วันที่ไฟล์ยังใช้ได้"),
            text_field("เวลาล่าสุดที่ยังใช้ได้", "last_good_time", "เวลาที่ไฟล์ยังใช้ได้", "เช่น 11:00"),
        ],
        height=820,
    )
    build_checks(
        doc,
        width,
        [
            ("ลบไฟล์หรือโฟลเดอร์โดยไม่ตั้งใจ", "sym_deleted", "อาการ ลบไฟล์"),
            ("ไฟล์หายหลังคัดลอกหรือย้าย", "sym_moved", "อาการ ไฟล์หายหลังย้าย"),
            ("ฟอร์แมตหรือลงวินโดวส์ใหม่", "sym_format", "อาการ ฟอร์แมต"),
            ("เปิดเครื่องแล้วไม่พบไดรฟ์", "sym_drive", "อาการ ไม่พบไดรฟ์"),
            ("ไฟล์เปิดไม่ได้หรือไฟล์เสีย", "sym_corrupt", "อาการ ไฟล์เสีย"),
            ("เครื่องดับหรือไฟดับระหว่างบันทึก", "sym_power", "อาการ ไฟดับ"),
            ("สงสัยมัลแวร์หรือแรนซัมแวร์", "sym_malware", "อาการ มัลแวร์"),
            ("อื่นๆ", "sym_other", "อาการ อื่นๆ"),
        ],
    )
    spacer(doc, 5)
    block_field(
        doc,
        width,
        "รายละเอียดอาการ",
        "incident_detail",
        "รายละเอียดอาการ",
        "เล่าสิ่งที่เกิดขึ้น ไฟล์อยู่โฟลเดอร์ใด และตอนนี้หน้าจอขึ้นข้อความว่าอะไร",
        height=1320,
    )
    spacer(doc, 5)
    block_field(
        doc,
        width,
        "สิ่งที่ทำไปแล้วก่อนแจ้งเจ้าหน้าที่",
        "actions_taken",
        "สิ่งที่ทำไปแล้ว",
        "เช่น ดูในถังรีไซเคิลแล้ว ยังไม่ได้ลงโปรแกรมกู้ข้อมูล",
        height=1100,
    )

    section_heading(doc, "05", "รายการไฟล์ที่ต้องการกู้")
    hint(doc, "ระบุให้ละเอียดที่สุด หากจำชื่อไม่ได้ ให้บอกเรื่องของไฟล์และโฟลเดอร์ที่เคยอยู่ หากมากกว่า 6 รายการ ให้คัดลอกแถวเพิ่ม")
    build_file_table(doc, width)

    section_heading(doc, "06", "การดำเนินการกู้ข้อมูล")
    hint(doc, "ส่วนนี้สำหรับเจ้าหน้าที่ ทำสำเนาดิสก์ก่อน แล้วบันทึกไฟล์ที่กู้ได้ลงคนละไดรฟ์")
    build_checks(
        doc,
        width,
        [
            ("ถังรีไซเคิล", "method_recycle", "วิธี ถังรีไซเคิล"),
            ("ประวัติเวอร์ชันของไฟล์", "method_versions", "วิธี ประวัติเวอร์ชัน"),
            ("สำเนาสำรองของบริษัท", "method_backup", "วิธี สำเนาสำรอง"),
            ("ซอฟต์แวร์กู้ข้อมูล", "method_software", "วิธี ซอฟต์แวร์"),
            ("ทำอิมเมจดิสก์แล้วกู้จากสำเนา", "method_image", "วิธี อิมเมจดิสก์"),
            ("ส่งผู้ให้บริการภายนอก", "method_lab", "วิธี ผู้ให้บริการภายนอก"),
        ],
    )
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            text_field("ชื่อซอฟต์แวร์และเวอร์ชัน", "software_name", "ซอฟต์แวร์", "เช่น ชื่อโปรแกรมและเวอร์ชัน"),
            text_field("ที่เก็บไฟล์อิมเมจ", "image_location", "ที่เก็บอิมเมจ"),
        ],
    )
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            text_field("ดิสก์ต้นทาง ห้ามเขียนทับ", "source_disk", "ดิสก์ต้นทาง", "รุ่น ความจุ และอาการ"),
            text_field("ปลายทางที่เก็บไฟล์ที่กู้ได้", "destination", "ปลายทางไฟล์ที่กู้ได้", "ต้องเป็นคนละไดรฟ์กับต้นทาง"),
        ],
    )
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            date_field("วันที่เริ่มกู้", "recover_start_date", "วันที่เริ่มกู้"),
            text_field("เวลาเริ่ม", "recover_start_time", "เวลาเริ่มกู้", "เช่น 10:00"),
            date_field("วันที่เสร็จ", "recover_end_date", "วันที่เสร็จ"),
            text_field("เวลาเสร็จ", "recover_end_time", "เวลาเสร็จ", "เช่น 16:40"),
        ],
        height=820,
    )
    spacer(doc, 5)
    block_field(
        doc,
        width,
        "บันทึกขั้นตอน",
        "work_log",
        "บันทึกขั้นตอน",
        "ลำดับสิ่งที่ทำ ผลแต่ละขั้น และปัญหาที่พบ",
        height=1400,
    )

    section_heading(doc, "07", "ผลการกู้คืน")
    field_row(doc, width, [choice_field("ผลสรุป", "outcome", "ผลสรุป", OUTCOME)])
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [
            text_field("จำนวนที่ขอ", "count_requested", "จำนวนที่ขอ", "0"),
            text_field("กู้ได้ครบ", "count_full", "จำนวนที่กู้ได้ครบ", "0"),
            text_field("ได้บางส่วน", "count_partial", "จำนวนที่ได้บางส่วน", "0"),
            text_field("กู้ไม่ได้", "count_failed", "จำนวนที่กู้ไม่ได้", "0"),
        ],
        height=780,
    )
    spacer(doc, 5)
    field_row(
        doc,
        width,
        [text_field("ตำแหน่งที่จัดเก็บไฟล์ที่กู้ได้", "output_location", "ตำแหน่งไฟล์ที่กู้ได้", "เช่น \\\\fileserver\\restore\\FR-2569-001")],
    )
    spacer(doc, 5)
    block_field(
        doc,
        width,
        "ข้อจำกัดของไฟล์ที่ได้คืน",
        "limitations",
        "ข้อจำกัดของไฟล์",
        "เช่น ชื่อไฟล์เพี้ยน เปิดไม่ได้บางไฟล์ หรือได้แต่โฟลเดอร์ไม่ครบ",
        height=1100,
    )
    spacer(doc, 5)
    block_field(
        doc,
        width,
        "คำแนะนำถึงผู้แจ้ง",
        "advice",
        "คำแนะนำ",
        "เช่น ให้สำรองไฟล์นี้ขึ้นโฟลเดอร์ส่วนกลาง และอย่าใช้งานดิสก์ลูกเดิมต่อ",
        height=1100,
    )

    page_break(doc)
    section_heading(doc, "08", "รูปภาพประกอบ")
    hint(
        doc,
        "คลิกกรอบรูป แล้วคลิกไอคอนรูปภาพที่มุมกรอบ เพื่อเลือกไฟล์ PNG หรือ JPG หากไม่เห็นไอคอน ให้คลิกขวาที่รูปแล้วเลือกเปลี่ยนรูปภาพ รูปที่เกินสี่รูปให้วางในกรอบรูปเพิ่มเติม",
    )
    build_photos(doc, width)

    section_heading(doc, "09", "การส่งมอบและลงนาม")
    hint(doc, "ส่งไฟล์ที่กู้ได้เฉพาะผู้มีสิทธิ และลบสำเนาชั่วคราวออกจากเครื่องที่ใช้กู้เมื่อปิดงาน")
    field_row(
        doc,
        width,
        [
            choice_field("ช่องทางส่งมอบ", "delivery_method", "ช่องทางส่งมอบ", DELIVERY),
            text_field("ชื่อผู้รับไฟล์", "receiver_name", "ชื่อผู้รับไฟล์"),
            date_field("วันที่ส่งมอบ", "delivery_date", "วันที่ส่งมอบ"),
        ],
    )
    spacer(doc, 5)
    build_checks(
        doc,
        width,
        [
            ("ผู้รับเปิดตรวจไฟล์ตัวอย่างแล้ว", "sample_checked", "ตรวจไฟล์ตัวอย่างแล้ว"),
            ("ลบสำเนาชั่วคราวออกจากเครื่องที่ใช้กู้แล้ว", "temp_deleted", "ลบสำเนาชั่วคราวแล้ว"),
        ],
    )
    spacer(doc, 5)
    block_field(
        doc,
        width,
        "หมายเหตุการส่งมอบ",
        "delivery_note",
        "หมายเหตุการส่งมอบ",
        "เช่น ส่งเฉพาะโฟลเดอร์บัญชีปี 2568 ไม่รวมไฟล์ส่วนตัวที่ปนมา",
        height=1000,
    )
    page_break(doc)
    p = para(doc, before=0, after=2, line=1.0, keep_next=True, keep_lines=True)
    write_text(p, "ลงนาม", size=14, bold=True, color=NAVY, thai=SERIF_TH, latin=SANS)
    border_paragraph(p, edge="bottom", color=BRASS, sz="10", space="1")
    hint(doc, "ผู้แจ้งลงชื่อเมื่อได้รับไฟล์แล้ว ผู้กู้ข้อมูลลงชื่อเมื่อส่งมอบ และหัวหน้าฝ่ายลงชื่อเมื่อปิดงาน")
    build_signatures(doc, width)
    spacer(doc, 8)
    add_text(
        doc,
        "ไฟล์ที่กู้คืนอาจมีข้อมูลส่วนบุคคลหรือข้อมูลลับของบริษัท ผู้ดำเนินการเปิดดูเฉพาะเท่าที่จำเป็นต่อการตรวจว่ากู้ได้จริง",
        size=8.5,
        color=MUTED,
        before=2,
        after=0,
        line=1.12,
    )

    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    doc.save(OUT)
    checkbox_font = subset_checkbox_font()
    embed_fonts(OUT, checkbox_font)
    validate(OUT)
    return OUT


if __name__ == "__main__":
    print(build())
