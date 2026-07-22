#!/usr/bin/env python3
"""Inspect PPTX template structure for FO-GI-39."""

from __future__ import annotations

import re
import sys
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

A_NS = "http://schemas.openxmlformats.org/drawingml/2006/main"
P_NS = "http://schemas.openxmlformats.org/presentationml/2006/main"
R_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"


def qname(ns: str, tag: str) -> str:
    return f"{{{ns}}}{tag}"


def inspect(path: Path) -> None:
    print(f"\n=== {path} ===")
    if not path.exists():
        print("NOT FOUND")
        return

    print(f"size: {path.stat().st_size:,} bytes")

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
        print(f"parts: {len(names)}, slides: {len(slides)}, charts: {len(charts)}")

        external = []
        for name in names:
            data = z.read(name).decode("utf-8", errors="ignore")
            if "sharepoint.com" in data.lower():
                external.append(name)
            if 'TargetMode="External"' in data:
                external.append(name)
        print(f"external refs: {len(set(external))}")
        for item in sorted(set(external))[:10]:
            print(f"  - {item}")

        pres = ET.fromstring(z.read("ppt/presentation.xml"))
        sld_ids = pres.find(qname(P_NS, "sldIdLst"))
        if sld_ids is not None:
            print(f"presentation slide refs: {len(sld_ids.findall(qname(P_NS, 'sldId')))}")

        pres_rels = z.read("ppt/_rels/presentation.xml.rels").decode("utf-8")
        slide_rels = re.findall(r'Target="slides/(slide\d+\.xml)"', pres_rels)
        print(f"presentation.xml.rels slides: {slide_rels}")

        missing_rels = []
        for slide in slides:
            rel = slide.replace("ppt/slides/", "ppt/slides/_rels/") + ".rels"
            if rel not in names:
                missing_rels.append(rel)
        if missing_rels:
            print("MISSING slide rels:")
            for m in missing_rels:
                print(f"  - {m}")

        print("\nSlide texts:")
        for slide in slides:
            root = ET.fromstring(z.read(slide))
            texts = [
                t.text.strip()
                for t in root.iter(qname(A_NS, "t"))
                if t.text and t.text.strip()
            ]
            rel_path = slide.replace("ppt/slides/", "ppt/slides/_rels/") + ".rels"
            chart_refs = []
            if rel_path in names:
                rel_xml = z.read(rel_path).decode("utf-8")
                chart_refs = re.findall(r"charts/(chart\d+\.xml)", rel_xml)
            print(f"  {slide.split('/')[-1]} charts={chart_refs} => {' | '.join(texts[:4])}")

        print("\nChart parts:")
        for chart in charts:
            num = re.search(r"chart(\d+)", chart).group(1)
            style = f"ppt/charts/style{num}.xml" in names
            colors = f"ppt/charts/colors{num}.xml" in names
            rel = f"ppt/charts/_rels/chart{num}.xml.rels" in names
            xml = z.read(chart).decode("utf-8", errors="ignore")
            has_cache = "numCache" in xml
            has_external = "externalData" in xml
            print(
                f"  chart{num}: style={style} colors={colors} rels={rel} "
                f"cache={has_cache} externalData={has_external}"
            )


if __name__ == "__main__":
    root = Path(__file__).resolve().parents[1]
    paths = [
        root / "storage" / "app" / "templates" / "operaciones" / "FO-GI-39-v7.template.pptx",
        root / "docs" / "FO-GI-39  V7. INFORME DE GESTIÓN OPERACIONES.pptx",
    ]
    for arg in sys.argv[1:]:
        paths.append(Path(arg))

    for path in paths:
        inspect(path)
