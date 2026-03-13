<template>
  <BModal @hide="onHide" :title="$t('export.title')" :cancel-title="$t('export.cancel')" v-model="modal" centered>
    <template v-slot:ok>
      <div class="d-grid d-md-block setting-footer">
        <button @click="onSubmit" :disabled="IS_SUBMITED || !columns || !columns.length" class="btn btn-primary"  name="submit">
          <template v-if="IS_SUBMITED">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            {{ $t('export.loading') }}
          </template>
          <template v-else> <i class="fa-solid fa-file-arrow-down"></i> {{ $t('export.download') }}</template>
        </button>
      </div>
    </template>
    <div class="form-group">
      <label class="form-label" for="date-range">{{ $t('export.lbl_date') }}</label>
      <flat-pickr v-model="date_range" :value="date_range" :config="config" id="date-range" class="form-control" />
    </div>

    <div class="form-group">
      <p>{{ $t('export.lbl_select_file_type') }}</p>
      <BFormRadioGroup
          v-model="file_type"
          :options="buttonsOptions"
          button-variant="outline-primary"
          name="radios-btn-default"
          buttons
        >
      </BFormRadioGroup>
    </div>
    <div class="form-group">
      <p>{{ $t('export.lbl_select_columns') }}</p>
      <BFormCheckboxGroup
          v-model="columns"
          :options="MODULE_COLUMNS"
          button-variant="outline-secondary"
          name="columns"
          stacked>
        </BFormCheckboxGroup>
    </div>
    <span class="text-danger">{{ errors.columns }}</span>
  </BModal>
</template>
<script setup>
import { ref, onMounted,computed} from 'vue'
import { useField, useForm } from 'vee-validate'
import { JSON_REQUEST_HEADER } from '@/helpers/utilities'
import flatPickr from 'vue-flatpickr-component';
import { useModel } from '@/helpers/hooks/bootstrap-components'
import { useI18n } from 'vue-i18n'
import * as yup from 'yup'
import * as moment from 'moment'

const { t } = useI18n()

const props = defineProps({
  exportUrl: { type: String },
  moduleName: { type: String },
  moduleColumnProp: { type: Array, default: () => [] },
})
const MODULE_COLUMNS = computed(() => {
  return props.moduleColumnProp.map(column => {
    // If column has a translationKey, use it for translation
    if (column.translationKey) {
      return {
        ...column,
        text: t(column.translationKey)
      }
    }
    // If column has a text property, try to translate it using a default pattern
    // or keep the original text if no translation key is provided
    if (column.text) {
      // Try to translate using export.columns.{text} pattern, fallback to original text
      const translationKey = `export.columns.${column.value || column.text.toLowerCase().replace(/\s+/g, '_')}`
      const translated = t(translationKey)
      // If translation exists (not the same as key), use it; otherwise use original text
      return {
        ...column,
        text: translated !== translationKey ? translated : column.text
      }
    }
    return column
  })
})

const IS_SUBMITED = ref(false)
// Get the current date
const currentDate = moment();
// Calculate the date for 3 months ago
const threeMonthsAgo = currentDate.clone().subtract(3, 'months');
const config = ref({
    mode: "range",
    dateFormat: 'Y-m-d'
});

// Validations
const validationSchema = yup.object({
  file_type: yup.string()
  .required(() => t('export.file_type_required')),
  date_range: yup.string()
  .required(() => t('export.date_range_required')),
  columns: yup.array()
    .min(1, () => t('export.columns_required'))
})

const { handleSubmit, errors, resetForm } = useForm({
  validationSchema
})

const { value: file_type } = useField('file_type')
const { value: date_range } = useField('date_range')
const { value: columns } = useField('columns')
date_range.value = []

//  Reset Form
const setFormData = (data) => {
  resetForm({
    values: {
      date_range: data.date_range,
      file_type: data.file_type,
      columns: data.columns,
    }
  })
}
const defaultDate = () => {
  return threeMonthsAgo.format('YYYY-MM-DD')+' to '+currentDate.format('YYYY-MM-DD')
}
const defaultData = () => {
  return {
    date_range: defaultDate(),
    file_type: 'csv',
    columns: MODULE_COLUMNS.value.map(({ value }) => value) || [],
  }

}


const modal = useModel(() => {}, 'export_modal')
const buttonsOptions = [
  {text: 'XLSX', value: 'xlsx'},
  {text: 'XLS', value: 'xls'},
  {text: 'ODS', value: 'ods'},
  {text: 'CSV', value: 'csv'},
  {text: 'PDF', value: 'pdf'},
  {text: 'HTML', value: 'html'},
]

const onSubmit = handleSubmit((values) => {
  IS_SUBMITED.value = true
  // Convert the values object into query parameters
  const filterValues = {}
  // Select all elements with data-filter="select" and get their values
  document.querySelectorAll('[data-filter="select"]').forEach(el => {
    if (el.value) {
      filterValues[el.name] = el.value
    }
  })
  // Capture DT search
  const searchInput = document.querySelector('.dt-search')
  if (searchInput && searchInput.value) {
    filterValues['search'] = searchInput.value
  }

  const queryParams = new URLSearchParams(Object.entries({...values, ...filterValues})).toString();
  const urlWithParams = `${props.exportUrl}?${queryParams}`;
  fetch(urlWithParams, {headers: JSON_REQUEST_HEADER}).then(async (res) => {
    if(res.status === 200) {
      const blob = await res.blob()
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${date_range.value}-${props.moduleName}.${values.file_type}` // Set the filename for the download

      // Append the anchor to the document and click it to start the download
      document.body.appendChild(a);
      a.click();

      // Clean up the temporary anchor and URL object
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      IS_SUBMITED.value = false
    }
  }).catch(() => {
    IS_SUBMITED.value = false
  })
})

onMounted(() => {
  setFormData(defaultData())
})
const onHide = () => {
  // console.log('on hide')
  // console.log(columns.value)
  // setFormData(defaultData())
}
</script>
