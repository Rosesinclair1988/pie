<?php
/**
 * 1997-2012 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to ecommerce@quadra-informatique.fr so we can send you a copy immediately.
 *
 *  @author Quadra Informatique <ecommerce@quadra-informatique.fr>
 *  @copyright 1997-2012 Quadra Informatique
 *  @version Release: $Revision: 2.0.6 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */

class Quadra_Atos_Model_Api_Files extends Quadra_Atos_Model_Abstract
{
    public function getPathfileName($merchand_id = null)
    {
        $path = Mage::getBaseDir('base') . DS . 'lib' . DS . 'atos' . DS;

        if (is_dir($path)) {
            if (isset($merchand_id)) {
                $pathfile = $path . 'path.' . $merchand_id;
            } else {
                $parmcom = self::getInstalledParmcom();
                $pathfile = $path . 'path.' . $parmcom[0]['value'];
            }


            if (!file_exists($pathfile)) {
                $content = '#########################################################################' . "\n";
                $content.= '#' . "\n";
                $content.= '# Pathfile' . "\n";
                $content.= '#' . "\n";
                $content.= '# Liste fichiers parametres utilisés par le module de paiement' . "\n";
                $content.= '#' . "\n";
                $content.= '#########################################################################' . "\n";
                $content.= "\n";
                $content.= '# ------------------------------------------------------------------------' . "\n";
                $content.= '# Chemin vers le répertoire des logos depuis le web alias' . "\n";
                $content.= '# Exemple pour le répertoire www.merchant.com/cyberplus/payment/logo/' . "\n";
                $content.= '# indiquer:' . "\n";
                $content.= '# ------------------------------------------------------------------------' . "\n";
                $content.= '#' . "\n";
                $content.= 'D_LOGO!' . Mage::getBaseUrl() . 'atos/i/m/g/!' . "\n";
                $content.= '#' . "\n";
                $content.= '#------------------------------------------------------------------------' . "\n";
                $content.= '#  Fichiers paramètres lies à l\'api cyberplus paiement' . "\n";
                $content.= '#------------------------------------------------------------------------' . "\n";
                $content.= '#' . "\n";
                $content.= '# Certificat du commercant' . "\n";
                $content.= '#' . "\n";
                $content.= 'F_CERTIFICATE!' . $path . 'certif!' . "\n";
                $content.= '#' . "\n";
                $content.= '# Fichier paramètre commercant' . "\n";
                $content.= '#' . "\n";
                $content.= 'F_PARAM!' . $path . 'parmcom!' . "\n";
                $content.= '#' . "\n";
                $content.= '# Fichier des paramètres communs' . "\n";
                $content.= '#' . "\n";
                $content.= 'F_DEFAULT!' . $pathfile . '!' . "\n";
                $content.= '#' . "\n";
                $content.= '# --------------------------------------------------------------------------' . "\n";
                $content.= '# End of file' . "\n";
                $content.= '# --------------------------------------------------------------------------' . "\n";

                if ($fp = fopen($pathfile, 'w')) {
                    fputs($fp, $content);
                    fclose($fp);
                }
            }
        }

        return $pathfile;
    }

    public function getCertificate()
    {
        $certificate = null;

        foreach (self::getInstalledCertificates() as $current) {
            if (!isset($current['test'])) {
                return $current;
            }

            if (!isset($certificate)) {
                $certificate = $current;
            }
        }

        return $certificate;
    }

    public function getInstalledCertificates()
    {
        $certificates = array();
        $path = Mage::getBaseDir('base') . DS . 'lib' . DS . 'atos';

        if (is_dir($path)) {
            $dir = dir($path);
            while ($file = $dir->read()) {
                $data = explode('.', $file);
                $n = sizeof($data) - 1;

                if ($data[0] == 'certif') {
                    $certificates[] = self::getCertificateInfo($data[$n]);
                }
            }

            sort($certificates);
            $dir->close();
        }

        return $certificates;
    }

    public function getCertificateInfo($id)
    {
        $certificates = self::getPredefinedCertificates();

        if (isset($certificates[$id])) {
            return $certificates[$id];
        }

        return array(
            'value' => $id,
            'label' => $id,
        );
    }

    public function getPredefinedCertificates()
    {
        $predefined = array(
            '013044876511111' => array(
                'value' => '013044876511111',
                'label' => Mage::helper('atos')->__('Test account eTransaction')
            ),
            '014213245611111' => array(
                'value' => '014213245611111',
                'label' => Mage::helper('atos')->__('Test account Sogenactif')
            ),
            '038862749811111' => array(
                'value' => '038862749811111',
                'label' => Mage::helper('atos')->__('Test account CyberPlus')
            ),
            '082584341411111' => array(
                'value' => '082584341411111',
                'label' => Mage::helper('atos')->__('Test account Mercanet')
            ),
            '014141675911111' => array(
                'value' => '014141675911111',
                'label' => Mage::helper('atos')->__('Test account Scelluis')
            ),
            '014295303911111' => array(
                'value' => '014295303911111',
                'label' => Mage::helper('atos')->__('Test account Sherlocks')
            ),
            '000000014005555' => array(
                'value' => '000000014005555',
                'label' => Mage::helper('atos')->__('Test account Aurore Cetelem')
            )
        );

        return $predefined;
    }

    public function getInstalledParmcom()
    {
        $parmcom = array();
        $path = Mage::getBaseDir('base') . DS . 'lib' . DS . 'atos';

        if (is_dir($path)) {
            $dir = dir($path);

            while ($file = $dir->read()) {

                $data = explode('.', $file);

                if (($data[0] == 'parmcom') && !file_exists($path . 'certif.fr.' . $data[1])) {
                    $parmcom[] = array(
                        'value' => $file,
                        'label' => $file
                    );
                }
            }

            sort($parmcom);
            $dir->close();
        }

        if (empty($parmcom)) {
            Mage::throwException('Parcom files doesn\'t exist !');
        }

        return $parmcom;
    }
}
