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
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
$port = config::byKey('port', 'sms');
$deamonRunning = sms::deamonRunning();
?>


<form class="form-horizontal">
    <fieldset>
        <?php
        if ($port != 'none') {
            if (!$deamonRunning) {
                echo '<div class="alert alert-danger">Le démon SMS ne tourne pas vérifier le port (' . $port . ' : ' . jeedom::getUsbMapping($port) . ', si vous venez de l\'arreter il redemarrera automatiquement dans 1 minute)</div>';
            } else {
                echo '<div class="alert alert-success">Le démon SMS est en marche</div>';
            }
        }
        ?>
        <div class="form-group">
            <label class="col-lg-4 control-label">Port SMS</label>
            <div class="col-lg-4">
                <select class="configKey form-control" data-l1key="port">
                    <option value="none">Aucun</option>
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
            <label class="col-lg-4 control-label">Code pin (laisser vide s'il n'y en a pas)</label>
            <div class="col-lg-4">
                <input type="password" class="configKey form-control" data-l1key="pin" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">Texte mode</label>
            <div class="col-lg-4">
                <input type="checkbox" class="configKey tooltips" data-l1key="text_mode" title='A utiliser si vous ne recevez pas de message (compatibilité avec un maximum de modem) mais enleve le support des SMS multiple et des caractères spéciaux' />
            </div>
        </div>
         <div class="form-group">
            <label class="col-lg-4 control-label">Force du signal</label>
            <div class="col-lg-4">
                <span class="configKey" data-l1key="signal_strengh" ></span> / 30
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">Arrêt/Redémarrage</label>
            <div class="col-lg-2">
                <a class="btn btn-warning" id="bt_stopEnOceanDeamon"><i class='fa fa-stop'></i> Arrêter/Redemarrer le démon</a> 
            </div>
        </div>
        <div class="form-group expertModeVisible">
            <label class="col-lg-4 control-label">Lancer en debug</label>
            <div class="col-lg-2">
                <a class="btn btn-danger" id="bt_launchSmsInDebug"><i class="fa fa-exclamation-triangle"></i> Lancer en mode debug</a> 
            </div>
        </div>
    </fieldset>
</form>

<script>
    $('#bt_stopEnOceanDeamon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/sms/core/ajax/sms.ajax.php", // url du fichier php
            data: {
                action: "stopDeamon",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                $('#div_alert').showAlert({message: 'Le démon a été correctement arreté il se relancera automatiquement dans 1 minute', level: 'success'});
                $('#ul_plugin .li_plugin[data-plugin_id=sms]').click();
            }
        });
    });

    $('#bt_launchSmsInDebug').on('click', function () {
        bootbox.confirm('Etes-vous sur de vouloir lancer le démon en mode debug ? N\'oubliez pas de le relancer en mode normale une fois terminé', function (result) {
            if (result) {
                $('#md_modal').dialog({title: "SMS en mode debug"});
                $('#md_modal').load('index.php?v=d&plugin=sms&modal=show.debug').dialog('open');
            }
        });
    });
</script>