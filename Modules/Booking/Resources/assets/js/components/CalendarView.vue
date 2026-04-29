<template>
  <div class="booking-calendar-view-root">
    <div class="booking-calendar-toolbar sticky-top bg-body py-2 mb-3 border-bottom px-1 rounded shadow-sm">
      <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
        <button type="button" class="btn btn-primary" @click="createBooking">
          <i class="fa-solid fa-calendar-plus me-1"></i>{{ $t('messages.booking_calendar_new_appointment') }}
        </button>
        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-end flex-grow-1">
          <div class="pagination-controls mb-0">
            <nav aria-label="Pagination">
              <ul class="pagination pagination-sm justify-content-end mb-0 flex-wrap">
                <li class="px-1">
                  <button
                    id="refresh"
                    type="button"
                    class="btn btn-primary btn-sm rounded"
                    data-bs-toggle="tooltip"
                    :title="$t('messages.calendar_refresh')"
                    @click="refreshCalendar"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M21.4799 12.2424C21.7557 12.2326 21.9886 12.4482 21.9852 12.7241C21.9595 14.8075 21.2975 16.8392 20.0799 18.5506C18.7652 20.3986 16.8748 21.7718 14.6964 22.4612C12.518 23.1505 10.1711 23.1183 8.01299 22.3694C5.85488 21.6205 4.00382 20.196 2.74167 18.3126C1.47952 16.4293 0.875433 14.1905 1.02139 11.937C1.16734 9.68346 2.05534 7.53876 3.55018 5.82945C5.04501 4.12014 7.06478 2.93987 9.30193 2.46835C11.5391 1.99683 13.8711 2.2599 15.9428 3.2175L16.7558 1.91838C16.9822 1.55679 17.5282 1.62643 17.6565 2.03324L18.8635 5.85986C18.945 6.11851 18.8055 6.39505 18.549 6.48314L14.6564 7.82007C14.2314 7.96603 13.8445 7.52091 14.0483 7.12042L14.6828 5.87345C13.1977 5.18699 11.526 4.9984 9.92231 5.33642C8.31859 5.67443 6.8707 6.52052 5.79911 7.74586C4.72753 8.97119 4.09095 10.5086 3.98633 12.1241C3.8817 13.7395 4.31474 15.3445 5.21953 16.6945C6.12431 18.0446 7.45126 19.0658 8.99832 19.6027C10.5454 20.1395 12.2278 20.1626 13.7894 19.6684C15.351 19.1743 16.7062 18.1899 17.6486 16.8652C18.4937 15.6773 18.9654 14.2742 19.0113 12.8307C19.0201 12.5545 19.2341 12.3223 19.5103 12.3125L21.4799 12.2424Z" fill="#ffffff"></path>
                      <path d="M20.0941 18.5594C21.3117 16.848 21.9736 14.8163 21.9993 12.7329C22.0027 12.4569 21.7699 12.2413 21.4941 12.2512L19.5244 12.3213C19.2482 12.3311 19.0342 12.5633 19.0254 12.8395C18.9796 14.283 18.5078 15.6861 17.6628 16.8739C16.7203 18.1986 15.3651 19.183 13.8035 19.6772C12.2419 20.1714 10.5595 20.1483 9.01246 19.6114C7.4654 19.0746 6.13845 18.0534 5.23367 16.7033C4.66562 15.8557 4.28352 14.9076 4.10367 13.9196C4.00935 18.0934 6.49194 21.37 10.008 22.6416C10.697 22.8908 11.4336 22.9852 12.1652 22.9465C13.075 22.8983 13.8508 22.742 14.7105 22.4699C16.8889 21.7805 18.7794 20.4073 20.0941 18.5594Z" fill="#ffffff"></path>
                    </svg>
                  </button>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <button type="button" class="page-link" :disabled="currentPage === 1" @click="prevPage">{{ $t('messages.previous') }}</button>
                </li>
                <li v-if="currentPage > 1" class="page-item">
                  <button type="button" class="page-link" @click="goToPage(currentPage - 1)">{{ currentPage - 1 }}</button>
                </li>
                <li class="page-item active" aria-current="page">
                  <span class="page-link">{{ currentPage }}</span>
                </li>
                <li v-if="currentPage < totalPages" class="page-item">
                  <button type="button" class="page-link" @click="goToPage(currentPage + 1)">{{ currentPage + 1 }}</button>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                  <button type="button" class="page-link" :disabled="currentPage === totalPages" @click="nextPage">{{ $t('messages.next') }}</button>
                </li>
              </ul>
            </nav>
          </div>
          <small class="text-muted d-none d-md-inline">{{ $t('messages.calendar_staff_pagination_hint') }}</small>
        </div>
      </div>
    </div>
    <div ref="calenderRef" class="booking-calendar-mount"></div>
    <booking-form :booking-type="bookingType"
                  :status-list="bookingStatus"
                  @onSubmit="onSubmitEvent"
                  :booking-data="bookingData"></booking-form>
  </div>
</template>
<script setup>
import { reactive, ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { createRequest } from '@/helpers/utilities'

import Calendar from '@event-calendar/core'
import DayGrid from '@event-calendar/day-grid'
import List from '@event-calendar/list'
import TimeGrid from '@event-calendar/time-grid'
import ResourceTimeGrid from '@event-calendar/resource-time-grid'
import Interaction from '@event-calendar/interaction'

const { t } = useI18n()

const currentPage = ref(1)
const perPage = ref(6)
import BookingForm from './BookingForm.vue'
import { INDEX_URL } from '../constant/booking'
import * as moment from 'moment'
import 'moment/locale/hr'
const totalEmployees = ref(0)

const totalPages = computed(() => Math.max(1, Math.ceil((totalEmployees.value || 0) / perPage.value)))

const props = defineProps({
  status: { type: String, required: true },
  slotDuration: { type: String },
  branchId: {type: [String , Number]},
  date: new Date()
})
let slotsDurations = '00:15'
if(props.slotDuration !== '') {
  slotsDurations = props.slotDuration
}
const bookingStatus = ref(JSON.parse(props.status))
const calenderRef = ref(null)
const calenderInit = ref(null)
const bookingType = ref('')
const bookingData = reactive({
  id: 0,
  start_date_time: null,
  employee_id: null,
  branch_id: props.branchId
})

const businessHours = ref({
  slotMinTime: '00:00:00',
  slotMaxTime: '23:59:59',
  scrollTime: '09:00:00'
})

let resizeListener = null

const updateCalendarHeight = () => {
  nextTick(() => {
    if (!calenderRef.value || !calenderInit.value) {
      return
    }
    const rect = calenderRef.value.getBoundingClientRect()
    const bottomPadding = 24
    const h = Math.floor(window.innerHeight - rect.top - bottomPadding)
    calenderInit.value.setOption('height', `${Math.max(400, h)}px`)
  })
}

const refreshCalendar = () => {
  if (calenderInit.value) {
    calenderInit.value.refetchEvents()
    nextTick(() => updateCalendarHeight())
  } else {
    window.location.reload()
  }
}

const setBooking = (info) => {
  bookingData.id = info.id || 0
  bookingData.employee_id = info?.resource?.id || null
  bookingData.start_date_time = info.date || null
}

const showBookingForm = (info) => {
  bookingType.value = 'CALENDER_BOOKING'
  const elem = document.getElementById('booking-form')
  setBooking(info)
  const form = bootstrap.Offcanvas.getOrCreateInstance(elem)
  form.show()
  document.querySelector('.offcanvas-backdrop')?.remove()
  updateBodyClass('show')
}

const hideBookingForm = () => {
  const elem = document.getElementById('booking-form')
  const form = bootstrap.Offcanvas.getOrCreateInstance(elem)
  form.hide()
  updateBodyClass('hide')
}

const updateBodyClass = (value = 'hide') => {
  if(value == 'show') {
    document.body.classList.add('calender-view')
  } else {
    document.body.classList.remove('calender-view')
  }
}

const createBooking = () => {
  bookingType.value = 'CREATE_BOOKING'
  showBookingForm({})
}
onUnmounted(() => {
  if (resizeListener) {
    window.removeEventListener('resize', resizeListener)
    resizeListener = null
  }
  const elem = document.getElementById('booking-form')
  if(elem !== null) {
    updateBodyClass('hide')
    elem.removeEventListener('hide.bs.offcanvas', function() {
      setBooking({})
      updateBodyClass('hide')
      bookingType.value = ''
    })
  }
})
onMounted(() => {
  moment.locale('hr')
  const elem = document.getElementById('booking-form')
  if(elem !== null) {
    elem.addEventListener('hide.bs.offcanvas', function() {
      setBooking({})
      updateBodyClass('hide')
      bookingType.value = ''
    })
    const bkid = new URL(location.href).searchParams.get('booking_id')
    if(bkid !== null && bkid !== undefined) {
      bookingType.value = 'CALENDER_BOOKING'
      showBookingForm({id: bkid})
    }
  }
  if (calenderRef !== null) {
    calenderInit.value = new Calendar({
      target: calenderRef.value,
      props: {
        plugins: [DayGrid, List, TimeGrid, ResourceTimeGrid, Interaction],
        options: {
          locale: 'hr',
          date: props.date,
          slotEventOverlap: false,
          dragScroll: false,
          view: 'resourceTimeGridDay',
          height: '600px',
          headerToolbar: {
            start: 'prev,next today',
            center: 'title',
            end: 'resourceTimeGridDay,resourceTimeGridWeek,listWeek'
          },
          buttonText: function (texts) {
            texts.today = t('messages.calendar_today')
            texts.resourceTimeGridDay = t('messages.calendar_day')
            texts.resourceTimeGridWeek = t('messages.calendar_week')
            texts.dayGridMonth = t('messages.calendar_month')
            texts.timeGridWeek = t('messages.calendar_week')
            texts.timeGridDay = t('messages.calendar_day')
            texts.listWeek = t('messages.calendar_list')
            return texts
          },
          allDayText: t('messages.calendar_all_day'),
          eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
          eventContent: function (data) {
            if(data.event.titleHTML !== undefined) {
              return {html: data.event.titleHTML + data.timeText}
            }
            return data.timeText
          },
          slotLabelFormat: function (data) {
            const minute = data.getMinutes()
            if (minute === 0) {
              return moment(data).format('HH:mm')
            }
            return ''
          },
          resources: [],
          scrollTime: businessHours.value.scrollTime,
          slotMinTime: businessHours.value.slotMinTime,
          slotMaxTime: businessHours.value.slotMaxTime,
          events: [],
          views: {
            timeGridWeek: { pointer: true },
            resourceTimeGridWeek: { pointer: true },
            resourceTimeGridDay: { pointer: true },
            listWeek: { pointer: true }
          },
          eventSources: [
            {
              events: async function () {
                const params = {
                  page: currentPage.value,
                  per_page: perPage.value
                }
                const events = await createRequest(INDEX_URL(params)).then((res) => {
                  const { employees, data, business_hours } = res
                  totalEmployees.value = res.total_count
                  calenderInit.value.setOption('resources', employees)

                  if (business_hours) {
                    businessHours.value = {
                      slotMinTime: business_hours.slotMinTime || '00:00:00',
                      slotMaxTime: business_hours.slotMaxTime || '23:59:59',
                      scrollTime: business_hours.scrollTime || '09:00:00'
                    }

                    if (calenderInit.value) {
                      calenderInit.value.setOption('slotMinTime', businessHours.value.slotMinTime)
                      calenderInit.value.setOption('slotMaxTime', businessHours.value.slotMaxTime)
                      calenderInit.value.setOption('scrollTime', businessHours.value.scrollTime)
                    }
                  }

                  return data
                })
                nextTick(() => updateCalendarHeight())
                return events
              }
            }
          ],
          dateClick: function (info) {
            showBookingForm(info)
          },
          select: function (info) {
            showBookingForm(info)
          },
          eventClick: function (info) {
            const updatedInfo = {
              id: info.event.id,
              resource: {id: info.event.resourceIds[0]},
              date: info.event.start
            }
            showBookingForm(updatedInfo)
          },
          eventStartEditable: false,
          slotDuration: slotsDurations,
          dayMaxEvents: true,
          nowIndicator: true,
          selectable: false
        }
      }
    })
    updateCalendarHeight()
    resizeListener = () => updateCalendarHeight()
    window.addEventListener('resize', resizeListener)
  }
})

const onSubmitEvent = () => {
  calenderInit.value.refetchEvents()
  nextTick(() => updateCalendarHeight())
}

watch(() => props.branchId, (newBranchId, oldBranchId) => {
  if (newBranchId !== oldBranchId && calenderInit.value) {
    bookingData.branch_id = newBranchId
    calenderInit.value.refetchEvents()
    nextTick(() => updateCalendarHeight())
  }
})

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    calenderInit.value.refetchEvents()
  }
}

const nextPage = () => {
  if (currentPage.value * perPage.value < totalEmployees.value) {
    currentPage.value++
    calenderInit.value.refetchEvents()
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    calenderInit.value?.refetchEvents()
    nextTick(() => updateCalendarHeight())
  }
}

</script>
<style >
@import '@event-calendar/core/index.css';
body {
  transition: width 400ms ease;
}
.booking-calendar-toolbar {
  z-index: 1019;
}
.calender-view {
  width: calc(100% - 382px);
  transition: width 400ms ease;
}
.ec-lines {
  width: unset;
  margin-left: 8px;
}
.ec-header .ec-day {
  overflow: inherit !important;
  height: inherit !important;
  line-height: inherit;
  min-height: inherit;
}
.ec-day.ec-today {
  background-color: var(--bs-body-bg);
}
.dark .ec-day.ec-today {
  background-color: #181818;
}
.ec-event{
  border-radius: 4px;
  border-left: 3px solid rgba(0, 0, 0, 0.15);
  border-bottom: 2px solid var(--bs-border-color);
  cursor: pointer;
}
.ec-body:not(.ec-compact) .ec-line:nth-child(even):after{
  border-bottom-style: solid;
}
.ec-line:not(:first-child):after {
  border-color: var(--bs-border-color);
}
.ec-header,.ec-all-day,.ec-body,.ec-days,.ec-day{
  border-color: var(--bs-border-color);
}
.ec-button, .ec-button:not(:disabled) {
  color: var(--bs-body-color);
  background-color: var(--bs-body-bg);
  border-color: var(--bs-border-color);
}
.dark .ec-button:not(:disabled):hover, .dark .ec-button.ec-active {
  border-color: var(--bs-border-color);
  background-color: var(--bs-body-bg);
}
.ec-icon.ec-prev:after, .ec-icon.ec-next:after {
  border-color: var(--bs-body-color);
}
.dark .ec-event {
  border-left-color: rgba(255, 255, 255, 0.2);
}
</style>
