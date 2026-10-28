{{-- Styles communs aux modèles d'impression des documents commerciaux (repris de print/pdf-sales). --}}
<style type="text/css">
    @page {
        margin: 350px 25px 50px 25px;
    }
    body {
        margin: 0px;
        border: 1px solid {{ $Factory->pdf_header_font_color }};
    }
    * {
        font-family: Verdana, Arial, sans-serif;
    }
    a {
        color: #000;
        text-decoration: none;
    }
    table {
        font-size: smaller;
        border-collapse: collapse;
    }
    table th {
        padding: 10px;
    }
    header { position: fixed; top: -350px; left: 0px; right: 0px; height: 350px; }
    footer { position: fixed; bottom: -50px; left: 0px; right: 0px; background-color: {{ $Factory->pdf_header_font_color }}; height: 50px; }
    footer .pagenum:before {
        content: counter(page);
    }
    .muted { color: #6c757d; }
    .line-main td { padding: 4px; border-top: 1px solid #ccc; vertical-align: top; }
    .line-detail td { padding: 2px 6px 8px 6px; vertical-align: top; }
    .line-section td { padding: 8px 6px 4px 6px; border-bottom: 1px solid #777; font-weight: bold; text-transform: uppercase; }
    .line-subtotal td { font-weight: bold; background-color: #f4f4f4; }
    .line-text td { font-style: italic; color: #333; }
    .plan-thumb { max-width: 130px; max-height: 130px; border: 1px solid #ccc; }
    .facts { font-size: 0.95em; }
    .facts strong { font-weight: bold; }
    .estimate { margin-top: 12px; page-break-inside: avoid; }
    .estimate h4 { margin: 0 0 4px 0; }
</style>
