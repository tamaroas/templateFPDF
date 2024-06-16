<?php

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
$original_image_path = "img/logo.png";
$watermark_image_path = "img/generate/logo.png";

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
$pdf->addContent("Voici un exemple de contenu avec un filigrane.");
$pdf->Output('I', 'output_with_watermark.pdf');

echo "PDF généré avec succès avec un filigrane.";
?>
