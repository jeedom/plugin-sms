<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<form class="form-horizontal">
    <fieldset>
        <legend><i class="icon loisir-darth"></i> {{Démon}}</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Port SMS}}</label>
            <div class="col-lg-4">
                <select class="configKey form-control" data-l1key="port">
                    <option value="none">{{Aucun}}</option>
                    <option value="auto">{{Auto}}</option>
                    <?php
foreach (jeedom::getUsbMapping() as $name => $value) {
	echo '<option value="' . $name . '">' . $name . ' (' . $value . ')</option>';
}
foreach (ls('/dev/', 'tty*') as $value) {
	echo '<option value="/dev/' . $value . '">/dev/' . $value . '</option>';
}
?>
               </select>
           </div>
       </div>
       <div class="form-group">
        <label class="col-sm-4 control-label">{{Vitesse de communication (bauds)}}</label>
        <div class="col-sm-2">
            <select class="configKey form-control" data-l1key="serial_rate" >
                <option value="115200">115200</option>
                <option value="9600">9600</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Code pin (laisser vide s'il n'y en a pas)}}</label>
        <div class="col-lg-4">
            <input type="password" class="configKey form-control" data-l1key="pin" />
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Texte mode}}</label>
        <div class="col-lg-4">
            <input type="checkbox" class="configKey" data-l1key="text_mode" title='{{A utiliser si vous ne recevez pas de message (compatibilité avec un maximum de modem) mais enleve le support des SMS multiple et des caractères spéciaux}}' />
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Découper les messages par paquet de caractères}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="maxChartByMessage" title='{{Par defaut 140}}' />
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Passerelle SMS / SMS Gateway (modifer cas d'erreur : CMS 330 SMSC number not set)}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="smsc" title='{{Utiliser le code #*#*4636#*#* sur un mobile pour trouver le SMSC de votre opérateur}}'/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Force du signal}}</label>
        <div class="col-lg-4">
            <span class="configKey" data-l1key="signal_strengh" ></span> / 30
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Réseaux}}</label>
        <div class="col-lg-4">
            <span class="configKey" data-l1key="network_name" ></span>
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Port socket interne}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="socketport" />
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-4 control-label">{{Cycle (s)}}</label>
        <div class="col-sm-2">
            <input class="configKey form-control" data-l1key="cycle" />
        </div>
    </div>
</fieldset>
</form>