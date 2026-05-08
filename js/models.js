/*global dotclear */
'use strict';

dotclear.ready(() => {
  const models = document.getElementById('models');
  if (!models) {
    return;
  }

  const selector = document.getElementById('s_html');
  const item = document.getElementById('e_html');
  const active_item = document.getElementById('a_html');

  const arlequin = {
    msg: {
      predefined_models: 'Generic models',
      select_model: 'Select a generic model:',
      user_defined: 'User defined',
    },

    models: new Array(),

    addModel(model_name, s_html, e_html, a_html) {
      const model = new Array(model_name, s_html, e_html, a_html);
      arlequin.models.push(model);
    },

    addDefault() {
      arlequin.addModel(data.msg.user_defined, selector.value, item.value, active_item.value);
    },

    drawInterface() {
      if (!arlequin.models.length) {
        return;
      }

      let res = '';
      res += `<p>${data.msg.select_model} `;
      res += '<select id="mt_model">';
      for (const i in arlequin.models) {
        res += `<option value="${i}">${arlequin.models[i][0]}</option>`;
      }
      res += '</select>';
      res += '</p>';

      return res;
    },

    selectModel(id) {
      if (!arlequin.models[id]) {
        return;
      }

      selector.value = arlequin.models[id][1];
      item.value = arlequin.models[id][2];
      active_item.value = arlequin.models[id][3];
    },
  };

  // Get data
  const data = dotclear.getData('arlequin');

  arlequin.addDefault();
  for (const model of data.models) {
    arlequin.addModel(model.name, model.s_html, model.e_html, model.a_html);
  }

  models.innerHTML = arlequin.drawInterface();

  const choice = document.getElementById('mt_model');

  choice.addEventListener('change', (event) => arlequin.selectModel(event.currentTarget.value));
});
