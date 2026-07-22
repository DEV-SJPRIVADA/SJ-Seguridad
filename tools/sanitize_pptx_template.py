#!/usr/bin/env python3
"""Build sanitized FO-GI-39 template with 9 FT-OP indicator slides."""

from __future__ import annotations

import re
import shutil
import sys
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

A_NS = "http://schemas.openxmlformats.org/drawingml/2006/main"
C_NS = "http://schemas.openxmlformats.org/drawingml/2006/chart"
P_NS = "http://schemas.openxmlformats.org/presentationml/2006/main"
R_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
REL_NS = "http://schemas.openxmlformats.org/package/2006/relationships"

EXTERNAL_REL_TYPES = {
    "http://schemas.openxmlformats.org/officeDocument/2006/relationships/oleObject",
    "http://schemas.openxmlformats.org/officeDocument/2006/relationships/package",
    "http://schemas.openxmlformats.org/officeDocument/2006/relationships/linkedPackage",
}

DEFAULT_TITLES = {
    "FT-OP-01": "PERSONAL CAPACITADO SJ",
    "FT-OP-02": "Servicios No Conformes",
    "FT-OP-03": "Siniestralidad",
    "FT-OP-04": "Eficacia en la supervision clientes SJ",
    "FT-OP-05": "Eficacia en la visita de clientes SJ",
    "FT-OP-06": "Estrategias para evitar materializacion",
    "FT-OP-07": "Eficacia elaboracion analisis de riesgos",
    "FT-OP-08": "Inventario puestos seguridad fisica",
    "FT-OP-09": "Inventario de armas",
}

# Output slide plan: cover + FT-OP-01..09 in order
SLIDE_PLAN = [
    {"kind": "cover"},
    {"code": "FT-OP-01", "src_slide": 2, "src_chart": 1},
    {"code": "FT-OP-02", "src_slide": 2, "src_chart": 1},
    {"code": "FT-OP-03", "src_slide": 2, "src_chart": 1},
    {"code": "FT-OP-04", "src_slide": 4, "src_chart": 3},
    {"code": "FT-OP-05", "src_slide": 3, "src_chart": 2},
    {"code": "FT-OP-06", "src_slide": 2, "src_chart": 1},
    {"code": "FT-OP-07", "src_slide": 5, "src_chart": 4},
    {"code": "FT-OP-08", "src_slide": 2, "src_chart": 1},
    {"code": "FT-OP-09", "src_slide": 2, "src_chart": 1},
]


def qname(ns: str, tag: str) -> str:
    return f"{{{ns}}}{tag}"


def register_namespaces() -> None:
    ET.register_namespace("", REL_NS)
    ET.register_namespace("a", A_NS)
    ET.register_namespace("c", C_NS)
    ET.register_namespace("p", P_NS)
    ET.register_namespace("r", R_NS)


def set_slide_texts(xml_bytes: bytes, texts: list[str]) -> bytes:
    root = ET.fromstring(xml_bytes)
    nodes = [node for node in root.iter(qname(A_NS, "t")) if node.text is not None]
    for idx, value in enumerate(texts):
        if idx < len(nodes):
            nodes[idx].text = value
    return ET.tostring(root, encoding="utf-8", xml_declaration=True)


def strip_external_data_from_chart(xml_bytes: bytes) -> bytes:
    root = ET.fromstring(xml_bytes)

    for parent in list(root.iter()):
        for child in list(parent):
            if child.tag.split("}")[-1] == "externalData":
                parent.remove(child)

    for tag in ("numRef", "strRef"):
        for ref in root.iter(qname(C_NS, tag)):
            fmla = ref.find(qname(C_NS, "f"))
            if fmla is not None:
                ref.remove(fmla)

    return ET.tostring(root, encoding="utf-8", xml_declaration=True)


def build_chart_rels(chart_num: int) -> bytes:
    xml = (
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        f'<Relationship Id="rId1" Type="http://schemas.microsoft.com/office/2011/relationships/chartStyle" Target="style{chart_num}.xml"/>'
        f'<Relationship Id="rId2" Type="http://schemas.microsoft.com/office/2011/relationships/chartColorStyle" Target="colors{chart_num}.xml"/>'
        "</Relationships>"
    )
    return xml.encode("utf-8")


def build_indicator_slide_rels(chart_num: int, src_slide_rels: bytes) -> bytes:
    """Keep layout/image rels from source slide; point chart rel to output chart."""
    root = ET.fromstring(src_slide_rels)
    new_root = ET.Element(f"{{{REL_NS}}}Relationships")

    for rel in root:
        rel_type = rel.get("Type", "")
        target = rel.get("Target", "")

        if "relationships/chart" in rel_type:
            clone = ET.SubElement(new_root, f"{{{REL_NS}}}Relationship")
            clone.set("Id", rel.get("Id", "rId3"))
            clone.set("Type", rel_type)
            clone.set("Target", f"../charts/chart{chart_num}.xml")
            continue

        if rel_type in EXTERNAL_REL_TYPES or rel.get("TargetMode") == "External":
            continue

        clone = ET.SubElement(new_root, f"{{{REL_NS}}}Relationship")
        for attr, value in rel.attrib.items():
            clone.set(attr, value)

    return ET.tostring(new_root, encoding="utf-8", xml_declaration=True)


def build_cover_slide_rels(src_slide_rels: bytes) -> bytes:
    root = ET.fromstring(src_slide_rels)
    new_root = ET.Element(f"{{{REL_NS}}}Relationships")

    for rel in root:
        rel_type = rel.get("Type", "")
        if "relationships/chart" in rel_type:
            continue
        if rel_type in EXTERNAL_REL_TYPES or rel.get("TargetMode") == "External":
            continue

        clone = ET.SubElement(new_root, f"{{{REL_NS}}}Relationship")
        for attr, value in rel.attrib.items():
            clone.set(attr, value)

    return ET.tostring(new_root, encoding="utf-8", xml_declaration=True)


def placeholder(code: str, field: str) -> str:
    token = code.replace("-", "_")
    return f"{{{{{field}_{token}}}}}"


def rebuild_template(source: Path, destination: Path) -> None:
    register_namespaces()

    with zipfile.ZipFile(source, "r") as zin:
        original: dict[str, bytes] = {info.filename: zin.read(info.filename) for info in zin.infolist()}

    # Base package: keep everything except old slides/charts we rebuild
    skip_pattern = re.compile(
        r"^ppt/slides/slide\d+\.xml$|"
        r"^ppt/slides/_rels/slide\d+\.xml\.rels$|"
        r"^ppt/charts/chart\d+\.xml$|"
        r"^ppt/charts/style\d+\.xml$|"
        r"^ppt/charts/colors\d+\.xml$|"
        r"^ppt/charts/_rels/chart\d+\.xml\.rels$"
    )

    entries: dict[str, bytes] = {
        name: data for name, data in original.items() if not skip_pattern.match(name)
    }

    chart_sources: dict[int, tuple[int, int]] = {}

    for out_index, plan in enumerate(SLIDE_PLAN, start=1):
        if plan.get("kind") == "cover":
            slide_xml = original["ppt/slides/slide1.xml"]
            entries[f"ppt/slides/slide{out_index}.xml"] = set_slide_texts(
                slide_xml,
                ["{{REPORT_TITLE}}", "{{MONTH_NAME}}", "{{YEAR}}"],
            )
            entries[f"ppt/slides/_rels/slide{out_index}.xml.rels"] = build_cover_slide_rels(
                original["ppt/slides/_rels/slide1.xml.rels"]
            )
            continue

        code = plan["code"]
        src_slide = plan["src_slide"]
        src_chart = plan["src_chart"]
        out_chart = out_index - 1  # charts 1..9

        chart_sources[out_chart] = (src_chart, src_chart)

        entries[f"ppt/slides/slide{out_index}.xml"] = set_slide_texts(
            original[f"ppt/slides/slide{src_slide}.xml"],
            [
                placeholder(code, "INDICATOR_TITLE"),
                placeholder(code, "INDICATOR_NARRATIVE"),
            ],
        )
        entries[f"ppt/slides/_rels/slide{out_index}.xml.rels"] = build_indicator_slide_rels(
            out_chart,
            original[f"ppt/slides/_rels/slide{src_slide}.xml.rels"],
        )

    # Build charts 1..9
    for out_chart in range(1, 10):
        plan = SLIDE_PLAN[out_chart]  # index aligns: plan[1] is FT-OP-01
        src_chart = plan["src_chart"]

        entries[f"ppt/charts/chart{out_chart}.xml"] = strip_external_data_from_chart(
            original[f"ppt/charts/chart{src_chart}.xml"]
        )
        entries[f"ppt/charts/style{out_chart}.xml"] = original[f"ppt/charts/style{src_chart}.xml"]
        entries[f"ppt/charts/colors{out_chart}.xml"] = original[f"ppt/charts/colors{src_chart}.xml"]
        entries[f"ppt/charts/_rels/chart{out_chart}.xml.rels"] = build_chart_rels(out_chart)

    # Rebuild presentation slide order 1..10
    pres = ET.fromstring(entries["ppt/presentation.xml"])
    sld_id_lst = pres.find(qname(P_NS, "sldIdLst"))
    if sld_id_lst is None:
        raise RuntimeError("sldIdLst missing")

    for child in list(sld_id_lst):
        sld_id_lst.remove(child)

    slide_rid_start = 20
    sld_id_start = 256

    rel_parts: list[str] = []
    for i in range(1, 11):
        rid = f"rId{slide_rid_start + i - 1}"
        rel_parts.append(
            f'<Relationship Id="{rid}" '
            f'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" '
            f'Target="slides/slide{i}.xml"/>'
        )

        sld = ET.SubElement(sld_id_lst, qname(P_NS, "sldId"))
        sld.set("id", str(sld_id_start + i - 1))
        sld.set(qname(R_NS, "id"), rid)

    entries["ppt/presentation.xml"] = ET.tostring(pres, encoding="utf-8", xml_declaration=True)

    pres_rels = original["ppt/_rels/presentation.xml.rels"].decode("utf-8")
    pres_rels = re.sub(
        r'<Relationship Id="rId\d+" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide\d+\.xml"/>',
        "",
        pres_rels,
    )
    pres_rels = pres_rels.replace("</Relationships>", "".join(rel_parts) + "</Relationships>")
    entries["ppt/_rels/presentation.xml.rels"] = pres_rels.encode("utf-8")

    # Content types for slides 1-10 and charts 1-9
    ct = entries["[Content_Types].xml"].decode("utf-8")
    ct = re.sub(
        r'<Override PartName="/ppt/slides/slide\d+\.xml" ContentType="application/vnd\.openxmlformats-officedocument\.presentationml\.slide\+xml"/>',
        "",
        ct,
    )
    ct = re.sub(
        r'<Override PartName="/ppt/charts/chart\d+\.xml" ContentType="application/vnd\.openxmlformats-officedocument\.drawingml\.chart\+xml"/>',
        "",
        ct,
    )
    ct = re.sub(
        r'<Override PartName="/ppt/charts/style\d+\.xml" ContentType="application/vnd\.openxmlformats-officedocument\.drawingml\.chartStyle\+xml"/>',
        "",
        ct,
    )
    ct = re.sub(
        r'<Override PartName="/ppt/charts/colors\d+\.xml" ContentType="application/vnd\.openxmlformats-officedocument\.drawingml\.chartColorStyle\+xml"/>',
        "",
        ct,
    )

    overrides = []
    for i in range(1, 11):
        overrides.append(
            f'<Override PartName="/ppt/slides/slide{i}.xml" '
            f'ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>'
        )
    for i in range(1, 10):
        overrides.append(
            f'<Override PartName="/ppt/charts/chart{i}.xml" '
            f'ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>'
        )
        overrides.append(
            f'<Override PartName="/ppt/charts/style{i}.xml" '
            f'ContentType="application/vnd.openxmlformats-officedocument.drawingml.chartStyle+xml"/>'
        )
        overrides.append(
            f'<Override PartName="/ppt/charts/colors{i}.xml" '
            f'ContentType="application/vnd.openxmlformats-officedocument.drawingml.chartColorStyle+xml"/>'
        )

    ct = ct.replace("</Types>", "".join(overrides) + "</Types>")
    entries["[Content_Types].xml"] = ct.encode("utf-8")

    destination.parent.mkdir(parents=True, exist_ok=True)
    if destination.exists():
        backup = destination.with_suffix(".broken.pptx")
        shutil.copy2(destination, backup)
        print(f"Backup previous template: {backup}")

    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_DEFLATED) as zout:
        # Preserve original order where possible for stability
        written = set()
        for name in original.keys():
            if name in entries:
                zout.writestr(name, entries[name])
                written.add(name)
        for name in sorted(entries.keys()):
            if name not in written:
                zout.writestr(name, entries[name])

    print(f"Saved: {destination}")


def verify(path: Path) -> bool:
    ok = True
    with zipfile.ZipFile(path, "r") as z:
        names = z.namelist()
        slides = sorted(
            [n for n in names if re.match(r"ppt/slides/slide\d+\.xml", n)],
            key=lambda x: int(re.search(r"slide(\d+)", x).group(1)),
        )
        charts = sorted(
            [n for n in names if re.match(r"ppt/charts/chart\d+\.xml", n)],
            key=lambda x: int(re.search(r"chart(\d+)", x).group(1)),
        )

        print(f"slides={len(slides)} charts={len(charts)}")

        for name in names:
            data = z.read(name).decode("utf-8", errors="ignore")
            if "sharepoint.com" in data.lower() or 'TargetMode="External"' in data:
                print(f"FAIL external ref in {name}")
                ok = False

        for slide in slides:
            rel = slide.replace("ppt/slides/", "ppt/slides/_rels/") + ".rels"
            if rel not in names:
                print(f"FAIL missing rels for {slide}")
                ok = False

            root = ET.fromstring(z.read(slide))
            texts = [
                t.text.strip()
                for t in root.iter(qname(A_NS, "t"))
                if t.text and t.text.strip()
            ]
            rel_xml = z.read(rel).decode("utf-8") if rel in names else ""
            chart_refs = re.findall(r"charts/(chart\d+\.xml)", rel_xml)
            print(f"  {slide.split('/')[-1]} charts={chart_refs} texts={texts}")

        for chart in charts:
            num = re.search(r"chart(\d+)", chart).group(1)
            for part in (f"ppt/charts/style{num}.xml", f"ppt/charts/colors{num}.xml", f"ppt/charts/_rels/chart{num}.xml.rels"):
                if part not in names:
                    print(f"FAIL missing {part}")
                    ok = False

    if ok:
        print("VERIFY OK")
    return ok


if __name__ == "__main__":
    root = Path(__file__).resolve().parents[1]
    source = root / "docs" / "FO-GI-39  V7. INFORME DE GESTIÓN OPERACIONES.pptx"
    dest = root / "storage" / "app" / "templates" / "operaciones" / "FO-GI-39-v7.template.pptx"

    if len(sys.argv) >= 2:
        source = Path(sys.argv[1])
    if len(sys.argv) >= 3:
        dest = Path(sys.argv[2])

    rebuild_template(source, dest)
    if not verify(dest):
        sys.exit(1)
