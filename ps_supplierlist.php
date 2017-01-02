<?php
/*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_Supplierlist extends Module implements WidgetInterface
{
    protected $templateFile;

    public function __construct()
    {
        $this->name = 'ps_supplierlist';
        $this->tab = 'front_office_features';
        $this->version = '1.0.3';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans(
            'Supplier list',
            array(),
            'Modules.Supplierlist.Admin'
        );
        $this->description = $this->trans(
            'Adds a block with a list of your product suppliers on your shop.',
            array(),
            'Modules.Supplierlist.Admin'
        );
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:ps_supplierlist/views/templates/hook/ps_supplierlist.tpl';
    }

    public function install()
    {
        Configuration::updateValue('SUPPLIER_DISPLAY_TYPE', 'supplier_text');
        Configuration::updateValue('SUPPLIER_DISPLAY_TEXT_NB', 5);

        return parent::install()
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayRightColumn')
            && $this->registerHook('actionObjectSupplierDeleteAfter')
            && $this->registerHook('actionObjectSupplierAddAfter')
            && $this->registerHook('actionObjectSupplierUpdateAfter');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('SUPPLIER_DISPLAY_TYPE')
            && Configuration::deleteByName('SUPPLIER_DISPLAY_TEXT_NB');
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitBlockSuppliers')) {
            $type = Tools::getValue('SUPPLIER_DISPLAY_TYPE');
            $text_nb = (int)Tools::getValue('SUPPLIER_DISPLAY_TEXT_NB');

            if ('supplier_text' === $type && !Validate::isUnsignedInt($text_nb)) {
                $errors[] = $this->trans(
                    'Invalid number of elements.',
                    array(),
                    'Modules.Supplierlist.Admin'
                );
            } elseif (!in_array($type, array('supplier_text', 'supplier_form'))) {
                $errors[] = $this->trans(
                    'Please activate at least one type of list.',
                    array(),
                    'Modules.Supplierlist.Admin'
                );
            } else {
                Configuration::updateValue('SUPPLIER_DISPLAY_TYPE', $type);
                Configuration::updateValue('SUPPLIER_DISPLAY_TEXT_NB', $text_nb);
                $this->_clearCache('*');
            }

            if (isset($errors) && sizeof($errors)) {
                $output .= $this->displayError(implode('<br />', $errors));
            } else {
                $output .= $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
            }
        }

        return $output.$this->renderForm();
    }

    public function hookActionObjectSupplierUpdateAfter($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionObjectSupplierAddAfter($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionObjectSupplierDeleteAfter($params)
    {
        $this->_clearCache('*');
    }

    public function _clearCache($template, $id_cache = null, $id_compile = null)
    {
        return parent::_clearCache($this->templateFile);
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Type of display', array(), 'Modules.Supplierlist.Admin'),
                        'name' => 'SUPPLIER_DISPLAY_TYPE',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array(
                                    'id' => 'supplier_text',
                                    'name' => $this->trans('Use a plain-text list', array(), 'Modules.Supplierlist.Admin'),
                                ),
                                array(
                                    'id' => 'supplier_form',
                                    'name' => $this->trans('Use a drop-down list', array(), 'Modules.Supplierlist.Admin'),
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Number of elements to display', array(), 'Modules.Supplierlist.Admin'),
                        'desc' => $this->trans('Only apply in plain-text mode', array(), 'Modules.Supplierlist.Admin'),
                        'name' => 'SUPPLIER_DISPLAY_TEXT_NB',
                        'class' => 'fixed-width-xs',
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table =  $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang =
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') :
            0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBlockSuppliers';
        $helper->currentIndex = $this->context->link->getAdminLink(
                'AdminModules',
                false
            ) .
            '&configure=' . $this->name .
            '&tab_module=' . $this->tab .
            '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'SUPPLIER_DISPLAY_TYPE' => Tools::getValue('SUPPLIER_DISPLAY_TYPE', Configuration::get('SUPPLIER_DISPLAY_TYPE')),
            'SUPPLIER_DISPLAY_TEXT_NB' => Tools::getValue('SUPPLIER_DISPLAY_TEXT_NB', Configuration::get('SUPPLIER_DISPLAY_TEXT_NB')),
        );
    }

    public function renderWidget($hookName, array $configuration)
    {
        $cacheId = $this->getCacheId();
        $isCached = $this->isCached($this->templateFile, $cacheId);

        if (!$isCached) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $cacheId);
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $suppliers = Supplier::getSuppliers(
            false,
            (int)Context::getContext()->language->id,
            $active = true,
            $p = false,
            $n = false,
            $allGroups = false,
            $withProduct = true
        );

        if (!empty($suppliers)) {
            foreach ($suppliers as &$supplier) {
                $supplier['link'] = $this->context->link->getSupplierLink($supplier);
            }
        }

        return array(
            'suppliers' => $suppliers,
            'page_link' => $this->context->link->getPageLink('supplier'),
            'supplier_display_type' => Configuration::get('SUPPLIER_DISPLAY_TYPE'),
            'text_list_nb' => Configuration::get('SUPPLIER_DISPLAY_TEXT_NB'),
            'display_link_supplier' => Configuration::get('PS_DISPLAY_SUPPLIERS'),
        );
    }
}
