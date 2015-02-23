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
$deamonRunningMaster = sms::deamonRunning();
$deamonRunningSlave = array();
if (config::byKey('jeeNetwork::mode') == 'master') {
	foreach (jeeNetwork::byPlugin('sms') as $jeeNetwork) {
		try {
			$deamonRunningSlave[$jeeNetwork->getName()] = $jeeNetwork->sendRawRequest('deamonRunning', array('plugin' => 'sms'));
		} catch (Exception $e) {
			$deamonRunningSlave[$jeeNetwork->getName()] = false;
		}
	}
}
?>


<form class="form-horizontal">
    <fieldset>
        <?php
echo '<div class="form-group">';
echo '<label class="col-sm-4 control-label">{{Démon local}}</label>';
if (!$deamonRunningMaster) {
	echo '<div class="col-sm-1"><span class="label label-danger tooltips" title="{{Peut être normale si vous etes en deporté}}">NOK</span></div>';
} else {
	echo '<div class="col-sm-1"><span class="label label-success">OK</span></div>';
}
echo '</div>';
foreach ($deamonRunningSlave as $name => $status) {
	echo ' <div class="form-group"><label class="col-sm-4 control-label">{{Sur l\'esclave}} ' . $name . '</label>';
	if (!$status) {
		echo '<div class="col-sm-1"><span class="label label-danger">NOK</span></div>';
	} else {
		echo '<div class="col-sm-1"><span class="label label-success">OK</span></div>';
	}
	echo '</div>';
}
?>
  </fieldset>
</form>
<form class="form-horizontal">
    <fieldset>
        <legend>{{Démon local}}</legend>
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
        <label class="col-lg-4 control-label">{{Code pin (laisser vide s'il n'y en a pas)}}</label>
        <div class="col-lg-4">
            <input type="password" class="configKey form-control" data-l1key="pin" />
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Texte mode}}</label>
        <div class="col-lg-4">
            <input type="checkbox" class="configKey tooltips" data-l1key="text_mode" title='{{A utiliser si vous ne recevez pas de message (compatibilité avec un maximum de modem) mais enleve le support des SMS multiple et des caractères spéciaux}}' />
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Passerelle SMS / SMS Gateway (modifer cas d'erreur : CMS 330 SMSC number not set)}}</label>
        <div class="col-lg-4">
            <input class="configKey form-control" data-l1key="smsc" title='{{Utiliser le code #*#*4636#*#* sur un mobile pour trouver le SMSC de votre opérateur}}'/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Force du signal}}</label>
        <div class="col-lg-4">
            <span class="configKey" data-l1key="signal_strengh" ></span> / 30
        </div>
    </div>
    <div class="form-group expertModeVisible">
        <label class="col-lg-4 control-label">{{Port socket interne (modification dangereuse, doit etre le meme surtout les esclaves)}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="socketport" value='55002' />
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Gestion du démon}}</label>
        <div class="col-lg-8">
            <a class="btn btn-success" id="bt_restartSmsDeamon"><i class='fa fa-play'></i> {{(Re)démarrer}}</a>
            <a class="btn btn-danger" id="bt_stopSmsDeamon"><i class='fa fa-stop'></i> {{Arrêter}}</a>
            <a class="btn btn-warning" id="bt_launchSmsInDebug"><i class="fa fa-exclamation-triangle"></i> {{Lancer en mode debug}}</a>
        </div>
    </div>
</fieldset>
</form>

<?php
if (config::byKey('jeeNetwork::mode') == 'master') {
	foreach (jeeNetwork::byPlugin('sms') as $jeeNetwork) {
		?>
        <form class="form-horizontal slaveConfig" data-slave_id="<?php echo $jeeNetwork->getId();?>">
            <fieldset>
                <legend>{{Démon sur l'esclave}} <?php echo $jeeNetwork->getName()?></legend>
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{Port SMS}}</label>
                    <div class="col-lg-4">
                        <select class="slaveConfigKey form-control" data-l1key="port">
                            <option value="none">{{Aucun}}</option>
                            <option value="auto">{{Auto}}</option>
                            <?php
foreach ($jeeNetwork->sendRawRequest('jeedom::getUsbMapping') as $name => $value) {
			echo '<option value="' . $name . '">' . $name . ' (' . $value . ')</option>';
		}
		?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{Code pin (laisser vide s'il n'y en a pas)}}</label>
                    <div class="col-lg-4">
                        <input type="password" class="slaveConfigKey form-control" data-l1key="pin" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{Texte mode}}</label>
                    <div class="col-lg-4">
                        <input type="checkbox" class="slaveConfigKey tooltips" data-l1key="text_mode" title='{{A utiliser si vous ne recevez pas de message (compatibilité avec un maximum de modem) mais enleve le support des SMS multiple et des caractères spéciaux}}' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{Passerelle SMS / SMS Gateway (modifer cas d'erreur : CMS 330 SMSC number not set)}}</label>
                    <div class="col-lg-4">
                        <input class="slaveConfigKey form-control" data-l1key="smsc" title='{{Utiliser le code #*#*4636#*#* sur un mobile pour trouver le SMSC de votre opérateur}}'/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{Force du signal}}</label>
                    <div class="col-lg-4">
                        <span class="slaveConfigKey" data-l1key="signal_strengh" ></span> / 30
                    </div>
                </div>
                <div class="form-group expertModeVisible">
                    <label class="col-lg-4 control-label">{{Port socket interne (modification dangereuse, doit etre le meme surtout les esclaves)}}</label>
                    <div class="col-lg-2">
                        <input class="slaveConfigKey form-control" data-l1key="socketport" value='55002' />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{Gestion du démon}}</label>
                    <div class="col-lg-8">
                        <a class="btn btn-success bt_restartSMSDeamon"><i class='fa fa-play'></i> {{(Re)démarrer}}</a>
                        <a class="btn btn-danger bt_stopSMSDeamon"><i class='fa fa-stop'></i> {{Arrêter}}</a>
                        <a class="btn btn-warning bt_launchSMSInDebug"><i class="fa fa-exclamation-triangle"></i> {{Lancer en mode debug}}</a>
                    </div>
                </div>
            </fieldset>
        </form>

        <?php
}
}
?>

<script>
    $('.bt_restartSMSDeamon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/sms/core/ajax/sms.ajax.php", // url du fichier php
            data: {
                action: "restartSlaveDeamon",
                id : $(this).closest('.slaveConfig').attr('data-slave_id')
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
            $('#div_alert').showAlert({message: '{{Le démon a été correctement (re)demarré}}', level: 'success'});
            $('#ul_plugin .li_plugin[data-plugin_id=sms]').click();
        }
    });
    });

$('.bt_stopSMSDeamon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/sms/core/ajax/sms.ajax.php", // url du fichier php
            data: {
                action: "stopSlaveDeamon",
                id : $(this).closest('.slaveConfig').attr('data-slave_id')
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
            $('#div_alert').showAlert({message: '{{Le démon a été correctement arreté}}', level: 'success'});
            $('#ul_plugin .li_plugin[data-plugin_id=sms]').click();
        }
    });
    });

$('.bt_launchSMSInDebug').on('click', function () {
    var slave_id = $(this).closest('.slaveConfig').attr('data-slave_id');
    bootbox.confirm('{{Etes-vous sur de vouloir lancer le démon en mode debug ? N\'oubliez pas de le relancer en mode normale une fois terminé}}', function (result) {
        if (result) {
            $('#md_modal').dialog({title: "{{SMS en mode debug}}"});
            $('#md_modal').load('index.php?v=d&plugin=sms&modal=show.debug&slave_id='+slave_id).dialog('open');
        }
    });
});



    $('#bt_restartSmsDeamon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/sms/core/ajax/sms.ajax.php", // url du fichier php
            data: {
                action: "restartDeamon",
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
            $('#div_alert').showAlert({message: '{{Le démon a été correctement (re)démaré}}', level: 'success'});
            $('#ul_plugin .li_plugin[data-plugin_id=sms]').click();
        }
    });
    });

    $('#bt_stopSmsDeamon').on('click', function () {
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
            $('#div_alert').showAlert({message: '{{Le démon a été correctement arreté}}', level: 'success'});
            $('#ul_plugin .li_plugin[data-plugin_id=sms]').click();
        }
    });
    });

    $('#bt_launchSmsInDebug').on('click', function () {
        bootbox.confirm('{{Etes-vous sur de vouloir lancer le démon en mode debug ? N\'oubliez pas de le relancer en mode normale une fois terminé}}', function (result) {
            if (result) {
                $('#md_modal').dialog({title: "{{SMS en mode debug}}"});
                $('#md_modal').load('index.php?v=d&plugin=sms&modal=show.debug').dialog('open');
            }
        });
    });

    function sms_postSaveConfiguration(){
             $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/sms/core/ajax/sms.ajax.php", // url du fichier php
            data: {
                action: "restartDeamon",
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
            $('#ul_plugin .li_plugin[data-plugin_id=sms]').click();
        }
    });
         }
     </script>