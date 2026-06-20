<?php
/**
 * Api/ReportesApi.php
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Inmueble.php';

/* ── Autenticacion ── */
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'No autorizado'));
    exit;
}
if (!in_array($_SESSION['rol'], array('admin', 'publicador', 'agente'))) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Sin permisos'));
    exit;
}

/* ── FPDF ── */
require_once __DIR__ . '/../fpdf/fpdf.php';

/* ── Parametros ── */
$tipo           = isset($_GET['tipo'])       ? trim($_GET['tipo'])       : '';
$estado         = isset($_GET['estado'])     ? trim($_GET['estado'])     : '';
$operacion      = isset($_GET['operacion'])  ? trim($_GET['operacion'])  : '';
$filtroTipoReal = isset($_GET['tipo_filtro'])? trim($_GET['tipo_filtro']): '';

/* ── Modelo ── */
$inmuebleModel = new Inmueble();
$todos         = $inmuebleModel->listar();

/* HELPERS GLOBALES*/

function aplicarFiltros($inmuebles, $estado, $operacion, $tipoFiltro = '')
{
    $resultado = array();
    foreach ($inmuebles as $i) {
        $estI = isset($i['estado'])    ? $i['estado']    : '';
        $opI  = isset($i['operacion']) ? $i['operacion'] : '';
        $tipI = isset($i['tipo'])      ? $i['tipo']      : '';
        if ($estado      && $estI !== $estado)      continue;
        if ($operacion   && $opI  !== $operacion)   continue;
        if ($tipoFiltro  && $tipI !== $tipoFiltro)  continue;
        $resultado[] = $i;
    }
    return $resultado;
}

function formatCOP($v)
{
    return '$' . number_format((float)$v, 0, ',', '.');
}

function ic($s)
{
    // UTF-8 a ISO-8859-1 para FPDF
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)$s);
}

function contarPor($items, $campo, $valor)
{
    $n = 0;
    foreach ($items as $i) {
        if (isset($i[$campo]) && $i[$campo] === $valor) $n++;
    }
    return $n;
}

/* CLASE PDF BASE — DISENO CLARO/CLASICO */
class InmoVisionPDF extends FPDF
{
    public $reportTitle    = '';
    public $reportSubtitle = '';

    /* Paleta clara como metodos estaticos (compatible 7.4) */
    public static function cWhite()  { return array(255, 255, 255); }
    public static function cPage()   { return array(248, 250, 252); } // fondo de pagina (gris muy claro)
    public static function cCard()   { return array(255, 255, 255); } // tarjetas blancas
    public static function cBorder() { return array(226, 232, 240); } // bordes suaves
    public static function cBlue()   { return array(2,  132, 199); }
    public static function cOrange() { return array(234, 88,  12); }
    public static function cGreen()  { return array(22, 163,  74); }
    public static function cViolet() { return array(124, 58, 237); }
    public static function cAmber()  { return array(217,119,   6); }
    public static function cSlate()  { return array(100,116, 139); }
    public static function cText()   { return array(51,  65,  85); } // texto principal
    public static function cDark()   { return array(15,  23,  42); } // titulos fuertes
    public static function cAltRow() { return array(241, 245, 249); } // fila alterna

    public function __construct($title, $subtitle = '')
    {
        parent::__construct('P', 'mm', 'A4');
        $this->reportTitle    = $title;
        $this->reportSubtitle = $subtitle;
        $this->SetAutoPageBreak(true, 20);
        $this->SetMargins(15, 15, 15);
        $this->AliasNbPages();
    }

    public function Header()
    {
        $white  = self::cWhite();
        $dark   = self::cDark();
        $blue   = self::cBlue();
        $orange = self::cOrange();
        $slate  = self::cSlate();
        $border = self::cBorder();

        /* Fondo de pagina blanco */
        $this->SetFillColor($white[0], $white[1], $white[2]);
        $this->Rect(0, 0, 210, 297, 'F');

        /* Barra superior degradado azul/naranja */
        $this->SetFillColor($blue[0], $blue[1], $blue[2]);
        $this->Rect(0, 0, 105, 2.5, 'F');
        $this->SetFillColor($orange[0], $orange[1], $orange[2]);
        $this->Rect(105, 0, 105, 2.5, 'F');

        /* Titulo */
        $this->SetXY(15, 9);
        $this->SetFont('Helvetica', 'B', 16);
        $this->SetTextColor($dark[0], $dark[1], $dark[2]);
        $this->Cell(0, 8, ic('InmoVision 3D'), 0, 1);

        /* Nombre del reporte */
        $this->SetX(15);
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor($slate[0], $slate[1], $slate[2]);
        $this->Cell(0, 5, ic($this->reportTitle), 0, 1);

        if ($this->reportSubtitle) {
            $this->SetX(15);
            $this->SetFont('Helvetica', 'I', 8);
            $this->SetTextColor($slate[0], $slate[1], $slate[2]);
            $this->Cell(0, 4, ic($this->reportSubtitle), 0, 1);
        }

        /* Fecha */
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor($slate[0], $slate[1], $slate[2]);
        $this->SetXY(130, 11);
        $this->Cell(65, 5, ic('Generado: ' . date('d/m/Y H:i')), 0, 0, 'R');

        /* Linea separadora inferior */
        $this->SetDrawColor($border[0], $border[1], $border[2]);
        $this->SetLineWidth(0.3);
        $this->Line(15, 33, 195, 33);

        $this->Ln(14);
    }

    public function Footer()
    {
        $slate  = self::cSlate();
        $border = self::cBorder();
        $this->SetY(-15);
        $this->SetDrawColor($border[0], $border[1], $border[2]);
        $this->SetLineWidth(0.2);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor($slate[0], $slate[1], $slate[2]);
        $this->Cell(0, 10, ic('InmoVision 3D  -  Reporte generado automaticamente  -  Pagina ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }

    /* ── Titulo de seccion ── */
    public function sectionTitle($text, $color = null)
    {
        if ($color === null) $color = self::cBlue();
        $dark = self::cDark();
        $this->Ln(3);
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->Rect(15, $this->GetY(), 3, 6, 'F');
        $this->SetXY(20, $this->GetY());
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor($dark[0], $dark[1], $dark[2]);
        $this->Cell(0, 6, ic(strtoupper($text)), 0, 1);
        $this->Ln(2);
    }

    /* ── Caja de estadistica ── */
    public function statBox($x, $y, $w, $label, $value, $color)
    {
        $card   = self::cCard();
        $border = self::cBorder();
        $slate  = self::cSlate();

        $this->SetFillColor($card[0], $card[1], $card[2]);
        $this->SetDrawColor($border[0], $border[1], $border[2]);
        $this->SetLineWidth(0.25);
        $this->RoundedRect($x, $y, $w, 18, 2, 'FD');

        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->Rect($x, $y, 2.5, 18, 'F');

        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor($color[0], $color[1], $color[2]);
        $this->SetXY($x + 5, $y + 2);
        $this->Cell($w - 7, 7, ic($value), 0, 1);

        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor($slate[0], $slate[1], $slate[2]);
        $this->SetXY($x + 5, $y + 10);
        $this->Cell($w - 7, 5, ic($label), 0, 1);
    }

    /* ── Rectangulo redondeado ── */
    public function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k  = $this->k;
        $hp = $this->h;
        if ($style === 'F')                          $op = 'f';
        elseif ($style === 'FD' || $style === 'DF') $op = 'B';
        else                                         $op = 'S';
        $arc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_Arc($xc + $r * $arc, $yc - $r, $xc + $r, $yc - $r * $arc, $xc + $r, $yc);
        $xc = $x + $w - $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $arc, $xc + $r * $arc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $arc, $yc + $r, $xc - $r, $yc + $r * $arc, $xc - $r, $yc);
        $xc = $x + $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $arc, $xc - $r * $arc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    public function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $hp = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k, ($hp - $y1) * $this->k,
            $x2 * $this->k, ($hp - $y2) * $this->k,
            $x3 * $this->k, ($hp - $y3) * $this->k
        ));
    }

    /* ── Cabecera de tabla ── */
    public function tableHeader($cols)
    {
        $page = self::cPage();
        $blue = self::cBlue();
        $border = self::cBorder();
        $this->SetFillColor($page[0], $page[1], $page[2]);
        $this->SetTextColor($blue[0], $blue[1], $blue[2]);
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetDrawColor($border[0], $border[1], $border[2]);
        foreach ($cols as $col) {
            $label = $col[0];
            $w     = $col[1];
            $align = isset($col[2]) ? $col[2] : 'L';
            $this->Cell($w, 7, ic($label), 'B', 0, $align, true);
        }
        $this->Ln();
    }

    /* ── Fila de tabla ── */
    public function tableRow($cells, $alt = false)
    {
        $text   = self::cText();
        $altRow = self::cAltRow();
        $white  = self::cWhite();
        if ($alt) $this->SetFillColor($altRow[0], $altRow[1], $altRow[2]);
        else      $this->SetFillColor($white[0], $white[1], $white[2]);
        $this->SetTextColor($text[0], $text[1], $text[2]);
        $this->SetFont('Helvetica', '', 7.5);
        foreach ($cells as $cell) {
            $val   = isset($cell[0]) ? (string)$cell[0] : '';
            $w     = $cell[1];
            $align = isset($cell[2]) ? $cell[2] : 'L';
            $this->Cell($w, 6.5, ic($val), 0, 0, $align, true);
        }
        $this->Ln();
    }
}

/* ══════════════════════════════════════════════════════════
   1. INVENTARIO GENERAL
══════════════════════════════════════════════════════════ */
function reporteInventario($todos, $estado, $operacion)
{
    $inmuebles = aplicarFiltros($todos, $estado, $operacion);

    $parts = array();
    if ($estado)    $parts[] = ucfirst($estado);
    if ($operacion) $parts[] = ucfirst($operacion);
    $subtitulo = count($parts) ? implode(' · ', $parts) : 'Todos los inmuebles';

    $pdf   = new InmoVisionPDF('Reporte de Inventario General', $subtitulo);
    $pdf->AddPage();

    $total = count($inmuebles);
    $disp  = contarPor($inmuebles, 'estado', 'disponible');
    $vend  = contarPor($inmuebles, 'estado', 'vendido');
    $arr   = contarPor($inmuebles, 'estado', 'arrendado');

    $sumPrecios = 0;
    foreach ($inmuebles as $i) $sumPrecios += (float)(isset($i['precio']) ? $i['precio'] : 0);
    $prom = $total > 0 ? $sumPrecios / $total : 0;

    $boxW = 37; $gap = 4; $startX = 15;
    $y = $pdf->GetY();
    $pdf->statBox($startX,                    $y, $boxW, 'Total inmuebles', (string)$total,  InmoVisionPDF::cBlue());
    $pdf->statBox($startX + $boxW + $gap,     $y, $boxW, 'Disponibles',     (string)$disp,   InmoVisionPDF::cGreen());
    $pdf->statBox($startX + ($boxW+$gap) * 2, $y, $boxW, 'Vendidos',        (string)$vend,   InmoVisionPDF::cViolet());
    $pdf->statBox($startX + ($boxW+$gap) * 3, $y, $boxW, 'Precio promedio', formatCOP($prom),InmoVisionPDF::cAmber());
    $pdf->Ln(24);

    $pdf->sectionTitle('Listado de Inmuebles');
    $cols = array(
        array('#',          8,  'C'),
        array('Titulo',    52,  'L'),
        array('Tipo',      20,  'L'),
        array('Ubicacion', 38,  'L'),
        array('Operacion', 22,  'C'),
        array('Precio',    28,  'R'),
        array('Estado',    12,  'C'),
    );
    $pdf->tableHeader($cols);

    $idx = 0;
    foreach ($inmuebles as $i) {
        if ($pdf->GetY() > 255) { $pdf->AddPage(); $pdf->tableHeader($cols); }
        $titulo = mb_strimwidth(isset($i['titulo'])    ? $i['titulo']    : '', 0, 38, '...');
        $ubic   = mb_strimwidth(isset($i['ubicacion']) ? $i['ubicacion'] : '', 0, 28, '...');
        $pdf->tableRow(array(
            array(isset($i['idInmueble']) ? $i['idInmueble'] : '',       8,  'C'),
            array($titulo,                                                52, 'L'),
            array(ucfirst(isset($i['tipo'])      ? $i['tipo']      : ''),20, 'L'),
            array($ubic,                                                  38, 'L'),
            array(ucfirst(isset($i['operacion']) ? $i['operacion'] : ''),22, 'C'),
            array(formatCOP((float)(isset($i['precio']) ? $i['precio'] : 0)), 28, 'R'),
            array(ucfirst(isset($i['estado'])    ? $i['estado']    : ''),12, 'C'),
        ), $idx % 2 === 1);
        $idx++;
    }

    if ($total === 0) {
        $slate = InmoVisionPDF::cSlate();
        $pdf->SetFont('Helvetica', 'I', 9);
        $pdf->SetTextColor($slate[0], $slate[1], $slate[2]);
        $pdf->Cell(0, 10, ic('No se encontraron inmuebles con los filtros aplicados.'), 0, 1, 'C');
    }

    return $pdf;
}

/* ══════════════════════════════════════════════════════════
   2. POR TIPO
══════════════════════════════════════════════════════════ */
function reportePorTipo($todos, $filtroTipo)
{
    $inmuebles = $filtroTipo ? aplicarFiltros($todos, '', '', $filtroTipo) : $todos;
    $subtitulo = $filtroTipo ? 'Filtrado: ' . ucfirst($filtroTipo) : 'Todos los tipos';

    $pdf = new InmoVisionPDF('Reporte por Tipo de Inmueble', $subtitulo);
    $pdf->AddPage();

    /* Agrupar */
    $grupos = array();
    foreach ($inmuebles as $i) {
        $t = isset($i['tipo']) ? $i['tipo'] : 'otro';
        if (!isset($grupos[$t])) $grupos[$t] = array();
        $grupos[$t][] = $i;
    }
    ksort($grupos);

    $coloresPal = array(
        InmoVisionPDF::cBlue(), InmoVisionPDF::cOrange(),
        InmoVisionPDF::cGreen(), InmoVisionPDF::cViolet(), InmoVisionPDF::cAmber()
    );

    /* Resumen */
    $pdf->sectionTitle('Resumen por tipo');
    $boxW = 37; $gap = 4; $startX = 15;
    $y = $pdf->GetY();
    $col = 0; $ci = 0;
    foreach ($grupos as $tipo => $items) {
        $color = $coloresPal[$ci % count($coloresPal)];
        $x = $startX + ($col % 4) * ($boxW + $gap);
        if ($col > 0 && $col % 4 === 0) $y += 22;
        $pdf->statBox($x, $y, $boxW, ucfirst($tipo), count($items) . ' inm.', $color);
        $col++;
        $ci++;
    }
    $pdf->Ln(28);

    /* Detalle por tipo */
    $ci = 0;
    $tiposKeys = array_keys($grupos);
    foreach ($grupos as $tipo => $items) {
        $color = $coloresPal[$ci % count($coloresPal)];
        $pdf->sectionTitle(ucfirst($tipo) . ' (' . count($items) . ')', $color);

        $cols = array(
            array('#',          8,  'C'),
            array('Titulo',    60,  'L'),
            array('Ubicacion', 45,  'L'),
            array('Operacion', 22,  'C'),
            array('Precio',    28,  'R'),
            array('Estado',    17,  'C'),
        );
        $pdf->tableHeader($cols);

        $idx = 0;
        foreach ($items as $i) {
            if ($pdf->GetY() > 255) { $pdf->AddPage(); $pdf->tableHeader($cols); }
            $pdf->tableRow(array(
                array(isset($i['idInmueble']) ? $i['idInmueble'] : '',            8,  'C'),
                array(mb_strimwidth(isset($i['titulo'])    ? $i['titulo']    : '', 0, 45, '...'), 60, 'L'),
                array(mb_strimwidth(isset($i['ubicacion']) ? $i['ubicacion'] : '', 0, 32, '...'), 45, 'L'),
                array(ucfirst(isset($i['operacion']) ? $i['operacion'] : ''),     22, 'C'),
                array(formatCOP((float)(isset($i['precio']) ? $i['precio'] : 0)), 28, 'R'),
                array(ucfirst(isset($i['estado'])    ? $i['estado']    : ''),     17, 'C'),
            ), $idx % 2 === 1);
            $idx++;
        }
        $pdf->Ln(4);
        $ci++;
    }

    return $pdf;
}

/*ANALISIS DE PRECIOS */
function reportePrecios($todos, $operacion)
{
    $inmuebles = $operacion ? aplicarFiltros($todos, '', $operacion) : $todos;
    $subtitulo = $operacion ? 'Operacion: ' . ucfirst($operacion) : 'Venta y arriendo';

    $pdf = new InmoVisionPDF('Analisis de Precios', $subtitulo);
    $pdf->AddPage();

    $precios = array();
    foreach ($inmuebles as $i) $precios[] = (float)(isset($i['precio']) ? $i['precio'] : 0);
    $total = count($precios);
    $prom  = $total ? array_sum($precios) / $total : 0;
    $max   = $total ? max($precios) : 0;
    $min   = $total ? min($precios) : 0;

    $boxW = 37; $gap = 4; $startX = 15;
    $y = $pdf->GetY();
    $pdf->statBox($startX,                    $y, $boxW, 'Inmuebles analizados', (string)$total, InmoVisionPDF::cBlue());
    $pdf->statBox($startX + $boxW + $gap,     $y, $boxW, 'Precio promedio', formatCOP($prom),   InmoVisionPDF::cGreen());
    $pdf->statBox($startX + ($boxW+$gap) * 2, $y, $boxW, 'Precio maximo',   formatCOP($max),    InmoVisionPDF::cAmber());
    $pdf->statBox($startX + ($boxW+$gap) * 3, $y, $boxW, 'Precio minimo',   formatCOP($min),    InmoVisionPDF::cViolet());
    $pdf->Ln(25);

    /* Por tipo */
    $pdf->sectionTitle('Precios promedio por tipo');
    $porTipo = array();
    foreach ($inmuebles as $i) {
        $t = isset($i['tipo']) ? $i['tipo'] : 'otro';
        if (!isset($porTipo[$t])) $porTipo[$t] = array();
        $porTipo[$t][] = (float)(isset($i['precio']) ? $i['precio'] : 0);
    }
    ksort($porTipo);

    $cols = array(
        array('Tipo',          35, 'L'),
        array('Cantidad',      25, 'C'),
        array('Prom. precio',  38, 'R'),
        array('Max. precio',   38, 'R'),
        array('Min. precio',   38, 'R'),
        array('% del total',   26, 'C'),
    );
    $pdf->tableHeader($cols);
    $ci = 0;
    foreach ($porTipo as $tipo => $ps) {
        $pTot = count($ps);
        $pct  = $total > 0 ? round($pTot / $total * 100, 1) : 0;
        $pdf->tableRow(array(
            array(ucfirst($tipo),                       35, 'L'),
            array($pTot,                                25, 'C'),
            array(formatCOP(array_sum($ps) / $pTot),   38, 'R'),
            array(formatCOP(max($ps)),                  38, 'R'),
            array(formatCOP(min($ps)),                  38, 'R'),
            array($pct . '%',                           26, 'C'),
        ), $ci % 2 === 1);
        $ci++;
    }
    $pdf->Ln(6);

    /* Top 10 */
    $pdf->sectionTitle('Top 10 inmuebles por precio', InmoVisionPDF::cAmber());
    usort($inmuebles, function($a, $b) {
        $pa = (float)(isset($a['precio']) ? $a['precio'] : 0);
        $pb = (float)(isset($b['precio']) ? $b['precio'] : 0);
        return $pb > $pa ? 1 : ($pb < $pa ? -1 : 0);
    });
    $top = array_slice($inmuebles, 0, 10);

    $cols2 = array(
        array('#',          8,  'C'),
        array('Titulo',    55,  'L'),
        array('Tipo',      20,  'L'),
        array('Operacion', 22,  'C'),
        array('Precio',    35,  'R'),
        array('Estado',    40,  'L'),
    );
    $pdf->tableHeader($cols2);
    foreach ($top as $idx => $i) {
        $pdf->tableRow(array(
            array($idx + 1,                                                        8,  'C'),
            array(mb_strimwidth(isset($i['titulo'])    ? $i['titulo']    : '', 0, 38, '...'), 55, 'L'),
            array(ucfirst(isset($i['tipo'])      ? $i['tipo']      : ''),         20, 'L'),
            array(ucfirst(isset($i['operacion']) ? $i['operacion'] : ''),         22, 'C'),
            array(formatCOP((float)(isset($i['precio']) ? $i['precio'] : 0)),     35, 'R'),
            array(ucfirst(isset($i['estado'])    ? $i['estado']    : ''),         40, 'L'),
        ), $idx % 2 === 1);
    }

    return $pdf;
}

/* POR PUBLICADOR*/
function reportePublicadores($todos)
{
    $pdf = new InmoVisionPDF('Reporte por Publicador', 'Actividad de todos los agentes');
    $pdf->AddPage();

    /* Agrupar */
    $grupos = array();
    foreach ($todos as $i) {
        $nom = trim(
            (isset($i['publicador_nombre'])   ? $i['publicador_nombre']   : '') . ' ' .
            (isset($i['publicador_apellido']) ? $i['publicador_apellido'] : '')
        );
        if ($nom === '') $nom = 'Sin asignar';
        if (!isset($grupos[$nom])) $grupos[$nom] = array();
        $grupos[$nom][] = $i;
    }
    uasort($grupos, function($a, $b) {
        return count($b) - count($a);
    });

    $totalPub = count($grupos);
    $boxW = 55; $gap = 5; $startX = 15;
    $y = $pdf->GetY();
    $pdf->statBox($startX,                    $y, $boxW, 'Total publicadores',  (string)$totalPub, InmoVisionPDF::cBlue());
    $pdf->statBox($startX + $boxW + $gap,     $y, $boxW, 'Total inmuebles',    (string)count($todos), InmoVisionPDF::cGreen());
    $promPub = $totalPub > 0 ? number_format(count($todos) / $totalPub, 1) : '0';
    $pdf->statBox($startX + ($boxW+$gap) * 2, $y, $boxW, 'Promedio por agente', $promPub, InmoVisionPDF::cAmber());
    $pdf->Ln(24);

    /* Resumen */
    $pdf->sectionTitle('Resumen de actividad');
    $cols = array(
        array('Publicador',  55, 'L'),
        array('Inmuebles',   20, 'C'),
        array('Disponibles', 22, 'C'),
        array('Vendidos',    20, 'C'),
        array('Arrendados',  22, 'C'),
        array('Venta',       17, 'C'),
        array('Arriendo',    17, 'C'),
        array('Valor total', 27, 'R'),
    );
    $pdf->tableHeader($cols);
    $ci = 0;
    foreach ($grupos as $nombre => $items) {
        if ($pdf->GetY() > 255) { $pdf->AddPage(); $pdf->tableHeader($cols); }
        $disp  = contarPor($items, 'estado',    'disponible');
        $vend  = contarPor($items, 'estado',    'vendido');
        $arrI  = contarPor($items, 'estado',    'arrendado');
        $vta   = contarPor($items, 'operacion', 'venta');
        $aopI  = contarPor($items, 'operacion', 'arriendo');
        $sumV  = 0;
        foreach ($items as $i) $sumV += (float)(isset($i['precio']) ? $i['precio'] : 0);
        $pdf->tableRow(array(
            array(mb_strimwidth($nombre, 0, 35, '...'), 55, 'L'),
            array(count($items), 20, 'C'),
            array($disp,         22, 'C'),
            array($vend,         20, 'C'),
            array($arrI,         22, 'C'),
            array($vta,          17, 'C'),
            array($aopI,         17, 'C'),
            array(formatCOP($sumV), 27, 'R'),
        ), $ci % 2 === 1);
        $ci++;
    }

    /* Detalle */
    $pdf->Ln(4);
    $pdf->sectionTitle('Detalle por publicador');
    $coloresPal = array(
        InmoVisionPDF::cBlue(), InmoVisionPDF::cOrange(),
        InmoVisionPDF::cGreen(), InmoVisionPDF::cViolet()
    );
    $ci = 0;
    foreach ($grupos as $nombre => $items) {
        $color = $coloresPal[$ci % count($coloresPal)];
        if ($pdf->GetY() > 240) $pdf->AddPage();
        $pdf->sectionTitle($nombre . ' (' . count($items) . ' inmuebles)', $color);

        $cols2 = array(
            array('#',          8,  'C'),
            array('Titulo',    60,  'L'),
            array('Tipo',      22,  'L'),
            array('Operacion', 22,  'C'),
            array('Precio',    28,  'R'),
            array('Estado',    40,  'L'),
        );
        $pdf->tableHeader($cols2);
        $idx = 0;
        foreach ($items as $i) {
            if ($pdf->GetY() > 255) { $pdf->AddPage(); $pdf->tableHeader($cols2); }
            $pdf->tableRow(array(
                array(isset($i['idInmueble']) ? $i['idInmueble'] : '',                8,  'C'),
                array(mb_strimwidth(isset($i['titulo'])    ? $i['titulo']    : '', 0, 45, '...'), 60, 'L'),
                array(ucfirst(isset($i['tipo'])      ? $i['tipo']      : ''),         22, 'L'),
                array(ucfirst(isset($i['operacion']) ? $i['operacion'] : ''),         22, 'C'),
                array(formatCOP((float)(isset($i['precio']) ? $i['precio'] : 0)),     28, 'R'),
                array(ucfirst(isset($i['estado'])    ? $i['estado']    : ''),         40, 'L'),
            ), $idx % 2 === 1);
            $idx++;
        }
        $pdf->Ln(3);
        $ci++;
    }

    return $pdf;
}

/* ROUTER */
try {
    switch ($tipo) {
        case 'inventario':
            $pdf = reporteInventario($todos, $estado, $operacion);
            break;
        case 'por_tipo':
            $pdf = reportePorTipo($todos, $filtroTipoReal);
            break;
        case 'precios':
            $pdf = reportePrecios($todos, $operacion);
            break;
        case 'publicadores':
            $pdf = reportePublicadores($todos);
            break;
        default:
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array('error' => 'Tipo de reporte no valido: ' . $tipo));
            exit;
    }

    $filename = 'reporte_' . $tipo . '_' . date('Ymd_His') . '.pdf';
    $pdf->Output('D', $filename);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Error interno: ' . $e->getMessage()));
}