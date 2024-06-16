<?php

$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

header('Content-Type: text/html; charset=UTF-8');
setlocale(LC_ALL, 'fr_FR.UTF-8');

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/payroll360/class/payRoll360.dao.class.php');
dol_include_once('/payroll360/class/Payslip.class.php');
dol_include_once('/payroll360/class/employee.class.php');
dol_include_once('/payroll360/class/Charges.class.php');
dol_include_once('/payroll360/class/PayslipAccessory.class.php');

// Vérifiez que l'extension GD est activée
if (!extension_loaded('gd')) {
    die('L\'extension GD n\'est pas activée.');
}

// Fonction pour créer une image en filigrane avec GD
function degradeImage($logoPath, $outputPath, $opacity = 0.9) {
    // Charger l'image du logo
    $logo = imagecreatefrompng($logoPath);

    // Récupérer les dimensions de l'image
    $largeur = imagesx($logo);
    $hauteur = imagesy($logo);

    // Créer une nouvelle image avec un fond blanc
    $white_background = imagecreatetruecolor($largeur, $hauteur);
    $white = imagecolorallocate($white_background, 255, 255, 255);
    imagefill($white_background, 0, 0, $white);

    // Copier l'image d'origine sur l'image avec le fond blanc
    imagecopy($white_background, $logo, 0, 0, 0, 0, $largeur, $hauteur);
    imagedestroy($logo);  // Libérer la mémoire de l'image d'origine

    // Créer une couche blanche semi-transparente
    $transparent_layer = imagecreatetruecolor($largeur, $hauteur);
    $transparent_color = imagecolorallocatealpha($transparent_layer, 255, 255, 255, 127 * (1 - $opacity)); // Ajuster la transparence
    imagefill($transparent_layer, 0, 0, $transparent_color);

    // Fusionner la couche blanche semi-transparente avec l'image de sortie
    imagecopy($white_background, $transparent_layer, 0, 0, 0, 0, $largeur, $hauteur);

    // Enregistrer l'image résultante
    imagepng($white_background, $outputPath);

    // Libérer la mémoire
    imagedestroy($white_background);
    imagedestroy($transparent_layer);
}

// Chemins vers les images
$base_url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
$builpath = dol_buildpath('/viewimage.php?modulepart=mycompany&file='.urlencode('logos/'.$mysoc->logo).'', 1);
$imagePath = $base_url.$builpath;
$original_image_path = $base_url.$builpath;
$watermark_image_path = 'fpdf/'.$mysoc->logo;

// Créer une image en filigrane
degradeImage($original_image_path, $watermark_image_path);

// Utiliser FPDF pour créer un PDF avec l'image en filigrane
require_once ("fpdf/fpdf.php");

define('FPDF_FONTPATH', 'fpdf/font');

class PDFWithWatermark extends FPDF
{
    protected $watermark;

    function setWatermark($watermark)
    {
        $this->watermark = $watermark;
    }

    function Header()
    {
        if ($this->watermark) {
            // Obtenir les dimensions de l'image
            list($w, $h) = getimagesize($this->watermark);

            // Redimensionner l'image pour qu'elle s'adapte au document
            $maxWidth = $this->GetPageWidth() - 20;
            $maxHeight = $this->GetPageHeight() - 20;

            // Calculer le rapport d'aspect
            $ratio = min($maxWidth / $w, $maxHeight / $h);

            // Redimensionner l'image
            $newWidth = $w * $ratio;
            $newHeight = $h * $ratio;

            // Calculer la position pour centrer l'image
            $x = ($this->GetPageWidth() - $newWidth) / 2;
            $y = ($this->GetPageHeight() - $newHeight) / 2;

            // Afficher l'image redimensionnée et centrée
            $this->Image($this->watermark, $x, $y, $newWidth, $newHeight);
        }
    }

    function addContent($content)
    {
        $this->AddPage();
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 10, $content);
    }
}

$pdf = new PDFWithWatermark();
$pdf->setWatermark($watermark_image_path);
$pdf->addContent("Voici un exemple de contenu avec un filigrane en arrière-plan.");
$pdf->Output('I', 'output_with_watermark.pdf');

echo "PDF généré avec succès avec un filigrane.";
?>
