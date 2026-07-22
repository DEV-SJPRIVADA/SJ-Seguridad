#!/usr/bin/env python3
"""Extract chart prototype from FO-GI-39 source PPTX."""

from __future__ import annotations

import zipfile
from pathlib import Path

root = Path(__file__).resolve().parents[1]
dest = root / "storage/app/templates/operaciones/chart-prototype"
dest.mkdir(parents=True, exist_ok=True)

sources = [root / "storage/app/templates/operaciones/FO-GI-39-v7.template.pptx"]
sources.extend(root.glob("docs/FO-GI-39*.pptx"))

source = next(
    (path for path in sources if path.exists() and any(name.endswith("ppt/charts/chart1.xml") or name == "ppt/charts/chart1.xml" for name in zipfile.ZipFile(path).namelist())),
    None,
)
if source is None:
    raise SystemExit("No source PPTX with chart1.xml found.")

mapping = {
    "ppt/charts/chart1.xml": "chart.xml",
    "ppt/charts/style1.xml": "style.xml",
    "ppt/charts/colors1.xml": "colors.xml",
    "ppt/charts/_rels/chart1.xml.rels": "chart.rels.xml",
}

with zipfile.ZipFile(source) as z:
    slide2 = z.read("ppt/slides/slide2.xml").decode("utf-8")
    start = slide2.find("<p:graphicFrame>")
    end = slide2.find("</p:graphicFrame>") + len("</p:graphicFrame>")
    if start >= 0 and end > start:
        frame = slide2[start:end]
        # Keep only valid inline namespaces; drop Office ext blocks that break slide XML.
        if "ns3:" in frame or "ns4:" in frame:
            frame = (
                '<p:graphicFrame><p:nvGraphicFramePr><p:cNvPr id="{{FRAME_ID}}" name="{{FRAME_NAME}}"/>'
                '<p:cNvGraphicFramePr><a:graphicFrameLocks/></p:cNvGraphicFramePr><p:nvPr/></p:nvGraphicFramePr>'
                '<p:xfrm><a:off x="1296647" y="1061459"/><a:ext cx="10080000" cy="3960000"/></p:xfrm>'
                '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart">'
                '<c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" '
                'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
                'r:id="{{CHART_REL_ID}}"/></a:graphicData></a:graphic></p:graphicFrame>'
            )
        (dest / "graphic-frame.xml").write_text(frame, encoding="utf-8")

    for src, name in mapping.items():
        (dest / name).write_bytes(z.read(src))

print(f"Extracted chart prototype from {source} to {dest}")
