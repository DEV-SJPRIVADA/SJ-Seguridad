#!/usr/bin/env python3
"""Validate PPTX export for common corruption issues."""

from __future__ import annotations

import re
import sys
import zipfile
import xml.etree.ElementTree as ET
from pathlib import Path

REL_NS = "http://schemas.openxmlformats.org/package/2006/relationships"


def inspect(path: Path) -> int:
    errors: list[str] = []
    print(f"\n=== VALIDATE {path} ===")

    with zipfile.ZipFile(path, "r") as z:
        names = set(z.namelist())
        print(f"parts: {len(names)}")

        # XML parse all xml parts
        for name in sorted(names):
            if not name.endswith(".xml"):
                continue
            data = z.read(name)
            try:
                ET.fromstring(data)
            except ET.ParseError as exc:
                errors.append(f"XML parse error in {name}: {exc}")
                print(f"PARSE FAIL: {name}: {exc}")

        slides = sorted(
            [n for n in names if re.match(r"ppt/slides/slide\d+\.xml", n)],
            key=lambda x: int(re.search(r"slide(\d+)", x).group(1)),
        )

        for slide in slides:
            rel = slide.replace("ppt/slides/", "ppt/slides/_rels/") + ".rels"
            slide_xml = z.read(slide).decode("utf-8", errors="ignore")
            chart_refs = re.findall(r'r:id="(rId\d+)"', slide_xml)
            has_frame = "graphicFrame" in slide_xml
            bad_ns = "ns3:" in slide_xml or "ns4:" in slide_xml

            rel_ids: set[str] = set()
            rel_targets: dict[str, str] = {}
            if rel in names:
                rel_root = ET.fromstring(z.read(rel))
                for node in rel_root.findall(f"{{{REL_NS}}}Relationship"):
                    rid = node.attrib.get("Id", "")
                    target = node.attrib.get("Target", "")
                    rel_ids.add(rid)
                    rel_targets[rid] = target
                    if rid in rel_ids and list(rel_targets.keys()).count(rid) > 1:
                        pass
                # duplicate ids
                ids = [n.attrib.get("Id") for n in rel_root.findall(f"{{{REL_NS}}}Relationship")]
                dupes = {i for i in ids if ids.count(i) > 1}
                if dupes:
                    errors.append(f"{rel}: duplicate relationship ids {dupes}")

            for rid in chart_refs:
                if rid not in rel_ids:
                    errors.append(f"{slide}: chart references missing rel {rid}")

            if has_frame and not chart_refs:
                errors.append(f"{slide}: graphicFrame without chart r:id")

            if bad_ns:
                errors.append(f"{slide}: undefined ns3/ns4 prefixes in injected frame")

            charts_on_slide = [t for t in rel_targets.values() if "charts/chart" in t]
            print(
                f"{slide}: frame={has_frame} chart_refs={chart_refs} "
                f"chart_rels={charts_on_slide} rel_ids={sorted(rel_ids)} bad_ns={bad_ns}"
            )

        ct = z.read("[Content_Types].xml").decode("utf-8", errors="ignore")
        chart_parts = [n for n in names if re.match(r"ppt/charts/chart\d+\.xml", n)]
        for chart in chart_parts:
            part = "/" + chart
            if part not in ct:
                errors.append(f"Missing Content_Types override for {part}")

        print(f"charts: {len(chart_parts)}")

        formula_refs = [n for n in names if n.startswith("ppt/charts/chart") and n.endswith(".xml")]
        for chart in formula_refs:
            data = z.read(chart).decode("utf-8", errors="ignore")
            if "formulaRef" in data or "externalData" in data:
                errors.append(f"{chart} still contains broken chart references")

    if errors:
        print("\nERRORS:")
        for err in errors:
            print(f"  - {err}")
        return 1

    print("\nNo structural errors detected.")
    return 0


if __name__ == "__main__":
    path = Path(sys.argv[1] if len(sys.argv) > 1 else "storage/app/templates/operaciones/test-export.pptx")
    raise SystemExit(inspect(path))
