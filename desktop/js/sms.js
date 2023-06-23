
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

function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} };
  }
  if (isset(_cmd.logicalId) && _cmd.logicalId == 'generic_sms') {
    return;
  }
  if (!isset(_cmd.type) || !isset(_cmd.subType)) {
    // user is adding a new action message command
    _cmd.type = 'action';
    _cmd.subType = 'message';
  }

  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td>';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
  tr += '<div class="input-group">';
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">';
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
  tr += '</div>';
  if (_cmd.type == 'action') {
    tr += '<td>';
    tr += '<select class="form-control cmdAttr input-sm" data-l1key="configuration" data-l2key="user"></select>';
    tr += '</td>';
    tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="phonenumber"></td>';
  } else if (_cmd.type == 'info') {
    tr += '<td>';
    tr += '</td>';
    tr += '<td></td>';
  }
  tr += '<td>';
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '</td>';
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
  tr += '</td>';
  tr += '<td>';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="' + init(_cmd.type) + '" style="display : none;" />';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" value="' + init(_cmd.subType) + '" style="display : none;" />';
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
  tr += '</tr>';

  $('#table_cmd tbody').append(tr);
  var tr = $('#table_cmd tbody tr:last');
  jeedom.user.all({
    error: function (error) {
      $('#div_alert').showAlert({ message: error.message, level: 'danger' });
    },
    success: function (data) {
      var option = '<option value="">Aucun</option>';
      for (var i in data) {
        option += '<option value="' + data[i].id + '">' + data[i].login + '</option>';
      }
      tr.find('.cmdAttr[data-l1key=configuration][data-l2key=user]').empty().append(option);
      tr.setValues(_cmd, '.cmdAttr');
      modifyWithoutSave = false;
    }
  });
}

$('.eqLogicAttr[data-l2key="allowUnknownOrigin"]').on('change', function () {
  $('#autoAddNewNumber').toggle(this.checked);
}).change();

$('.eqLogicAttr[data-l2key="autoAddNewNumber"]').on('change', function () {
  $('#autoAddNewNumberWarning').toggle(this.checked);
}).change();