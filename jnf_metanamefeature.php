<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Jnf_Metanamefeature extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'jnf_metanamefeature';
        $this->tab = 'others';
        $this->version = '0.9';
        $this->author = 'Jairo J. Niño (jnfDev)';
        $this->need_instance = 1;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Meta-Name Feature');
        $this->description = $this->l('This module add a extra field to feature form in 
            BackOffice who be able to set a meta-name in all features created.
        ');

        $this->ps_versions_compliancy = array('min' => '1.7.1', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() &&
            $this->installDb() &&
            $this->registerHook('displayFeatureForm') &&
            $this->registerHook('actionFeatureSave') &&
            $this->registerHook('actionGetProductPropertiesAfter');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'jnf_metanamefeature` (
            `id_metaname` int(11) NOT NULL AUTO_INCREMENT,
            `id_feature` int(11) NOT NULL,
            `value` varchar(255) NOT NULL,
            PRIMARY KEY  (`id_metaname`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    /* Getter and Setter functions */
    public function setFeatureMetaName($meta_name, $id_feature)
    {
        // Meta name already exist?
        $exist = $this->getFeatureMetaName($id_feature);

        if(!$meta_name || $meta_name === ''){
            return true;
        }

        if ($exist) {
            $sql = 'UPDATE `'._DB_PREFIX_.'jnf_metanamefeature`
                    SET `value` = "'.pSQL($meta_name).'"
                    WHERE `id_feature` = ' . (int) $id_feature;

        } else {
            $sql = 'INSERT INTO `'._DB_PREFIX_.'jnf_metanamefeature` (`id_feature`, `value`)
                    VALUES ('. (int) $id_feature.', "'.pSQL($meta_name).'")';
        }

        return Db::getInstance()->execute($sql);
    }

    public function getFeatureMetaName($id_feature, $full = false)
    {
        $sql = 'SELECT `value`
                FROM `' . _DB_PREFIX_. 'jnf_metanamefeature`
                WHERE  `id_feature` = ' . (int) $id_feature; 

        return Db::getInstance()->getValue($sql);
    }

    public function getAllFeaturesByMetaName($id_product, $id_lang = null)
    {        
        $feature_by_metaname = [];
        $features = Product::getFeaturesStatic((int)$id_product);
        foreach ($features as $aFeature) {
            
            $id_feature =  (int) $aFeature['id_feature'];
            $id_feature_value = (int) $aFeature['id_feature_value'];
            
            // Get feature metaname
            $feature_metaname = trim($this->getFeatureMetaName($id_feature));
            if(!$feature_metaname || $feature_metaname === '') {
                continue;
            }

            if(!$id_lang) {
                $id_lang = $this->context->language->id;
            }

            // Get feature name 
            $feature_name = Feature::getFeature($id_lang, $id_feature);
            $feature_name = $feature_name['name'];
            $feature_by_metaname[$feature_metaname]['name'] = $feature_name;

            // Get Feature value 
            $feature_value = FeatureValue::getFeatureValue($id_lang, $id_feature_value);
            $feature_by_metaname[$feature_metaname]['value'] = $feature_value;

        }

        return $feature_by_metaname;
    }

    /* Hooks functions */

    public function hookDisplayFeatureForm($params)
    {
        $id_feature = $params['id_feature'];
        
        $this->context->smarty->assign(array(
            'id_feature' => $id_feature,
            'meta_name' => $this->getFeatureMetaName($id_feature)
        ));

        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/attr_form.tpl');
    }

    public function hookActionFeatureSave($params)
    {
        $id_feature = (int) $params['id_feature'];
        $form_value = Tools::getValue('meta-name');

        if (!$this->setFeatureMetaName($form_value, $id_feature)) {
            $this->context->controller->errors[] = $this->l('Error trying to saving Meta-Name');
        }
    }
    
    public function hookActionGetProductPropertiesAfter($params)
    {
        $id_lang =  $this->context->language->id;
        $id_product = $params['product']['id_product'];

        // Passing by reference the feature_by_metaname 
        $params['product']['feature_by_metaname'] = $this->getAllFeaturesByMetaName($id_product, $id_lang);
    }
}
