// Import Bootstrap and expose to window
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;
// import jquery from 'jquery'
// const jQuery = jquery;
import Snackbar from 'node-snackbar';
import 'node-snackbar/dist/snackbar.css';
import 'select2';
import 'select2/dist/css/select2.min.css';
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import Swal from 'sweetalert2';
import { Draggable } from '@fullcalendar/interaction';


(function (jQuery) {
  "use strict";
  jQuery(document).ready(function () {
    toolTip();
    callmodal();
    backToTop();
    const isRTL = document.documentElement.getAttribute('dir') === 'rtl';
    slickGeneral(isRTL);
    slickBanner(isRTL);
    customSlider(isRTL);
    productSlider(isRTL);
    saleProductSlider(isRTL);
    readmore();

    // herader sticky
    $(document).ready(function () {
      const $header = $('header');

      if ($header.length > 0) {
        const stickyOffset = $header.offset().top;

        $(window).on('scroll', function () {
          if ($(window).scrollTop() > stickyOffset) {
            $header.addClass('sticky');
          } else {
            $header.removeClass('sticky');
          }
        });
      } else {
        console.error("Header element not found");
      }
    });

    // general slider
    function slickGeneral(isRTL) {
      jQuery('.slick-general').each(function () {
        let slider = jQuery(this);
        let slideSpacing = slider.data("spacing");

        function addSliderSpacing(spacing) {
          slider.css('--spacing', `${spacing}px`);
        }

        addSliderSpacing(slideSpacing);

        slider.slick({
          slidesToShow: slider.data("items"),
          slidesToScroll: 1,
          speed: slider.data("speed"),
          autoplay: slider.data("autoplay"),
          centerMode: slider.data("center"),
          infinite: slider.data("infinite"),
          centerPadding: slider.data('centerpadding'),
          arrows: slider.data("navigation"),
          dots: slider.data("pagination"),
          prevArrow: "<span class='slick-prev'></span>",
          nextArrow: "<span class='slick-next'></span>",
          rtl: isRTL,
          responsive: [
            {
              breakpoint: 1600, // screen size below 1600
              settings: {
                slidesToShow: slider.data("items-desktop"),
              }
            },
            {
              breakpoint: 1400, // screen size below 1400
              settings: {
                slidesToShow: slider.data("items-laptop"),
              }
            },
            {
              breakpoint: 1200, // screen size below 1200
              settings: {
                slidesToShow: slider.data("items-tablet"),
              }
            },
            {
              breakpoint: 768, // screen size below 768
              settings: {
                slidesToShow: slider.data("items-mobile-sm"),
              }
            },
            {
              breakpoint: 576, // screen size below 576
              settings: {
                slidesToShow: slider.data("items-mobile"),
              }
            }
          ]
        });

        let active = slider.find(".slick-active");
        let slideItems = slider.find(".slick-track .slick-item");
        active.first().addClass("first");
        active.last().addClass("last");

        slider.on('afterChange', function (event, slick, currentSlide, nextSlide) {
          let active = slider.find(".slick-active");
          slideItems.removeClass("first last");
          active.first().addClass("first");
          active.last().addClass("last");
        });
      });
    }


    // function slickGeneral(isRTL) {
    //   jQuery('.slick-general').each(function () {
    //     let slider = jQuery(this);
    //     let slideSpacing = parseInt(slider.data("spacing")) || 0;

    //     function addSliderSpacing(spacing) {
    //       slider.css('--spacing', `${spacing}px`);
    //     }
    //     addSliderSpacing(slideSpacing);

    //     // Convert strings like "true"/"false" to real booleans
    //     function toBool(val) {
    //       return (val === true || val === "true");
    //     }

    //     slider.slick({
    //       slidesToShow: parseInt(slider.data("items")) || 1,
    //       slidesToScroll: 1,
    //       speed: parseInt(slider.data("speed")) || 500,
    //       autoplay: toBool(slider.data("autoplay")),
    //       centerMode: toBool(slider.data("center")),
    //       infinite: toBool(slider.data("infinite")),
    //       centerPadding: slider.data("centerpadding") || "0px",
    //       arrows: toBool(slider.data("navigation")),
    //       dots: toBool(slider.data("pagination")),
    //       prevArrow: "<span class='slick-prev'></span>",
    //       nextArrow: "<span class='slick-next'></span>",
    //       rtl: isRTL,
    //       lazyLoad: 'ondemand', // 🚀 add lazy loading
    //       responsive: [
    //         { breakpoint: 1600, settings: { slidesToShow: parseInt(slider.data("items-desktop")) || 4 } },
    //         { breakpoint: 1400, settings: { slidesToShow: parseInt(slider.data("items-laptop")) || 3 } },
    //         { breakpoint: 1200, settings: { slidesToShow: parseInt(slider.data("items-tablet")) || 2 } },
    //         { breakpoint: 768, settings: { slidesToShow: parseInt(slider.data("items-mobile-sm")) || 1 } },
    //         { breakpoint: 576, settings: { slidesToShow: parseInt(slider.data("items-mobile")) || 1 } }
    //       ]
    //     });

    //     let slideItems = slider.find(".slick-track .slick-item");

    //     slider.on('afterChange', function () {
    //       let active = slider.find(".slick-active");
    //       slideItems.removeClass("first last");
    //       active.first().addClass("first");
    //       active.last().addClass("last");
    //     });
    //   });
    // }

    // banner slider
    function slickBanner(isRTL) {
      jQuery('.slick-banner').each(function () {
        let bannerSlider = jQuery(this);
        bannerSlider.slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          autoplay: true,
          autoplaySpeed: 2000,
          arrows: true,
          dots: false,
          rtl: isRTL,
          responsive: [
            {
              breakpoint: 767,
              settings: {
                arrows: false,
                dots: true,
              }
            }
          ]
        });
      })
    }

    // product thumbnail slider
    function saleProductSlider(isRTL) {
      const $sliderNav = $('.slider-nav');
      const $sliderFor = $('.slider-for');

      if ($sliderNav.length && $sliderFor.length) {
        // Count the number of slides
        const slideCount = $sliderNav.children().length;

        // Optional: Apply spacing if data-spacing is defined
        const spacing = $sliderNav.data("spacing") || 0;
        $sliderNav.css('--spacing', `${spacing}px`);

        // Determine slider settings based on slide count
        const shouldSlide = slideCount > 4;

        $sliderFor.slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          arrows: false,
          fade: true,
          centerMode: false,
          asNavFor: shouldSlide ? $sliderNav : null, // Only connect if more than 4 slides
          rtl: isRTL,
          autoplay: true,
          autoplaySpeed: 3000,
          pauseOnHover: true
        });

        $sliderNav.on('init', function (event, slick) {
          $('.slider-nav .slick-slide.slick-current').addClass('is-active');
        });

        $sliderNav.slick({
          slidesToShow: shouldSlide ? 4 : slideCount, // Show all slides if 4 or less
          slidesToScroll: 1,
          asNavFor: shouldSlide ? $sliderFor : null,  // Only connect if more than 4 slides
          focusOnSelect: shouldSlide,                 // Only enable focus select if sliding
          variableWidth: true,
          rtl: isRTL,
          infinite: true,
          draggable: shouldSlide,                     // Disable dragging if 4 or less slides
          swipe: shouldSlide,                         // Disable swipe if 4 or less slides
          touchMove: shouldSlide,                     // Disable touch move if 4 or less slides
          arrows: false,                              // Always hide arrows
          centerMode: false,
          responsive: [
            {
              breakpoint: 375,
              settings: {
                slidesToShow: shouldSlide ? 3 : Math.min(3, slideCount),
                draggable: shouldSlide,
                swipe: shouldSlide,
                touchMove: shouldSlide
              }
            }
          ]
        });

        // If we have 4 or fewer slides, handle navigation manually
        if (!shouldSlide) {
          // Handle click events manually for thumbnail navigation
          $sliderNav.on('click', '.slick-slide', function (e) {
            e.preventDefault();
            const slideIndex = $(this).data('slick-index');

            // Change main slider
            $sliderFor.slick('slickGoTo', slideIndex);

            // Update active state manually
            $sliderNav.find('.slick-slide').removeClass('is-active');
            $(this).addClass('is-active');
          });

          // Listen to main slider changes to update thumbnail active state
          $sliderFor.on('afterChange', function (event, slick, currentSlide) {
            $sliderNav.find('.slick-slide').removeClass('is-active');
            $sliderNav.find('.slick-slide[data-slick-index="' + currentSlide + '"]').addClass('is-active');
          });
        }
      }
    }

    // product slider
    function productSlider(isRTL) {
      jQuery('.product-slider').slick({
        centerMode: true,
        centerPadding: '15px',
        slidesToShow: 5.5,
        infinite: false,
        focusOnSelect: true,
        rtl: isRTL,
        arrows: true,
        dots: true,
        responsive: [
          {
            breakpoint: 1199,
            settings: {
              slidesToShow: 4,
              dots: true,
            }
          },
          {
            breakpoint: 991,
            settings: {
              slidesToShow: 3,
              dots: true,
            }
          },
          {
            breakpoint: 768,
            settings: {
              slidesToShow: 1,
              dots: true,
            }
          }
        ]
      })
    }

    // select2

    // $(document).ready(function() {
    //     // Initialize all select elements with form-select class
    //     $('select.form-select').each(function() {
    //         // Get the placeholder from data attribute or use default
    //         const placeholder = $(this).data('placeholder') || 'Select an option';

    //         $(this).select2({
    //             width: '100%',
    //             placeholder: placeholder,
    //             allowClear: true,
    //             dropdownParent: $(this).parent(),

    //         });
    //     });
    // });
    const selectProfileModal = document.getElementById('selectProfileModal');
    if (selectProfileModal !== null) {
      selectProfileModal.addEventListener('shown.bs.modal', event => {
        selectProfileSlider();
      });
    } else {
      console.warn("Element with ID 'selectProfileModal' not found.");
    }
  });
})(jQuery);


$(document).ready(function () {
  $('.select2').select2({
    width: '100%',
    placeholder: 'Select an option',
    allowClear: true
  });
});

// tooltip
function toolTip() {
  // Tooltips
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
  const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl))
}

function callmodal() {
  const modalTriggerList = document.querySelectorAll('[data-bs-toggle="modal"]')
  const modalList = [...modalTriggerList].map(modalTriggerEl => new Modal(modalTriggerEl))
}

// readmore text
function readmore() {
  let buttons = document.querySelectorAll('.readmore-btn');
  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      let parent = button.closest('.readmore-wrapper');
      let readmoreText = parent.querySelector('.readmore-text');


      if (readmoreText.classList.contains('active')) {
        readmoreText.classList.remove('active');
        button.innerText = "Read More";
        button.classList.remove('bg-primary')
        button.classList.add('bg-dark')
      } else {
        readmoreText.classList.add('active');
        button.innerText = "Read Less";
        button.classList.remove('bg-dark')
        button.classList.add('bg-primary')
      }
    })
  });
}



// Dark Mode Toggle
function pageLoad() {
  var html = localStorage.getItem('data-bs-theme')
  const element = document.querySelector('html');

  if (html == null) {
    html = 'light'
  }

  if (html == 'dark') {
    if (!element.hasAttribute('data-lock-theme')) {
      jQuery('body').addClass('dark')
      $('.darkmode-logo').removeClass('d-none')
      $('.light-logo').addClass('d-none')
    }
  } else {
    jQuery('body').removeClass('dark')
    $('.darkmode-logo').addClass('d-none')
    $('.light-logo').removeClass('d-none')
  }
}

pageLoad()

const savedTheme = localStorage.getItem('data-bs-theme')
const element = document.querySelector('html');

if (savedTheme === 'dark') {
  if (!element.hasAttribute('data-lock-theme')) {
    $('html').attr('data-bs-theme', 'dark')
  }
} else {
  $('html').attr('data-bs-theme', 'light')
}
if (!localStorage.getItem('data-bs-theme')) {
  localStorage.setItem('data-bs-theme', 'light')
}
$('.change-mode').on('click', function () {
  const body = jQuery('body')
  const current = $('html').attr('data-bs-theme') || 'light'
  const isDark = current === 'dark'

  if (isDark) {
    $('html').attr('data-bs-theme', 'light')
    localStorage.setItem('data-bs-theme', 'light')
    body.removeClass('dark')
    $('.darkmode-logo').addClass('d-none')
    $('.light-logo').removeClass('d-none')
  } else {
    $('html').attr('data-bs-theme', 'dark')
    localStorage.setItem('data-bs-theme', 'dark')
    body.addClass('dark')
    $('.darkmode-logo').removeClass('d-none')
    $('.light-logo').addClass('d-none')
  }
})

// back to top
function backToTop() {
  const backToTop = document.getElementById("back-to-top");
  if (backToTop !== null && backToTop !== undefined) {
    backToTop.classList.add("animate__animated", "animate__fadeOut");
    backToTop.style.display = 'none'; // Always start hidden
    window.addEventListener("scroll", (e) => {
      if (document.documentElement.scrollTop > 250) {
        backToTop.style.display = 'block';
        backToTop.classList.remove("animate__fadeOut");
        backToTop.classList.add("animate__fadeIn");
      } else {
        backToTop.style.display = 'none';
        backToTop.classList.remove("animate__fadeIn");
        backToTop.classList.add("animate__fadeOut");
      }
    });
    // scroll body to 0px on click
    document.querySelector("#top").addEventListener("click", (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }
}

// custom slider
function customSlider(isRTL) {
  if (document.querySelectorAll(".custom-nav-slider").length) {
    const sliders = document.querySelectorAll('.custom-nav-slider');

    function slide(direction, e) {
      const container = e.target.closest("div").parentElement.getElementsByClassName("custom-nav-slider");
      const parent = e.target.closest("div").parentElement;
      slidescroll(container, direction, parent);
    }

    function slidescroll(container, direction, parent, is_vertical = false) {
      let scrollCompleted = 0;
      const rightArrow = parent ? parent.getElementsByClassName("right")[0] : null;
      const leftArrow = parent ? parent.getElementsByClassName("left")[0] : null;
      const maxScroll = parent ? container[0].scrollWidth - container[0].offsetWidth - 30 : null;

      const slideVar = setInterval(() => {
        if (direction === 'left') {
          if (is_vertical) {
            container[0].scrollTop -= 5;
          } else {
            container[0].scrollLeft -= 20;
          }
          if (parent) {
            rightArrow.style.display = "block";
            if (container[0].scrollLeft === 0)
              leftArrow.style.display = "none";
          }
        } else {
          if (is_vertical) {
            container[0].scrollTop += 5;
          } else {
            container[0].scrollLeft += 20;
          }
          if (parent) {
            leftArrow.style.display = "block";
            if (container[0].scrollLeft > maxScroll)
              rightArrow.style.display = "none";
          }
        }
        scrollCompleted += 10;
        if (scrollCompleted >= 100) {
          clearInterval(slideVar);
        }
      }, 40);
    }

    function enableSliderNav() {
      sliders.forEach((element) => {
        const left = element.parentElement.querySelector(".left");
        const right = element.parentElement.querySelector(".right");

        if (element.scrollWidth - element.clientWidth > 0) {
          right.style.display = "block";
          left.style.display = "block";
        } else {
          right.style.display = "none";
          left.style.display = "none";
        }

        // Attach event listeners to the left and right arrows
        if (left && right) {
          left.addEventListener('click', (e) => slide('left', e));
          right.addEventListener('click', (e) => slide('right', e));
        }
      });
    }

    function slideDrag(eslider) {
      let isDown = false;
      let startX;
      let scrollLeft;
      const maxScroll = eslider.scrollWidth - eslider.clientWidth - 20;
      const rightArrow = eslider.parentElement.getElementsByClassName("right")[0];
      const leftArrow = eslider.parentElement.getElementsByClassName("left")[0];

      eslider.addEventListener('mousedown', (e) => {
        isDown = true;
        eslider.classList.add('active');
        startX = e.pageX - eslider.offsetLeft;
        scrollLeft = eslider.scrollLeft;
      });

      eslider.addEventListener('mouseleave', () => {
        isDown = false;
        eslider.classList.remove('active');
      });

      eslider.addEventListener('mouseup', () => {
        isDown = false;
        eslider.classList.remove('active');
      });

      eslider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - eslider.offsetLeft;
        const walk = (x - startX) * 3; //scroll-fast
        eslider.scrollLeft = scrollLeft - walk;

        if (eslider.scrollLeft > maxScroll) {
          rightArrow.style.display = "none";
        } else {
          leftArrow.style.display = eslider.scrollLeft === 0 ? "none" : "block";
          rightArrow.style.display = "block";
        }
      });
    }

    // Initialize slider drag and navigation
    sliders.forEach((element) => {
      slideDrag(element);
    });
    enableSliderNav();

    // Re-enable navigation on resize
    window.addEventListener('resize', enableSliderNav);
  }
}

// snackbarMessage
const snackbarMessage = () => {
  const PRIMARY_COLOR = window.getComputedStyle(document.querySelector('html')).getPropertyValue('--bs-success').trim()
  const DANGER_COLOR = window.getComputedStyle(document.querySelector('html')).getPropertyValue('--bs-danger').trim()

  const successSnackbar = (message) => {
    Snackbar.show({
      text: message,
      pos: 'bottom-left',
      actionTextColor: PRIMARY_COLOR,
      duration: 2500
    })
  }
  window.successSnackbar = successSnackbar

  const errorSnackbar = (message) => {
    Snackbar.show({
      text: message,
      pos: 'bottom-left',
      actionTextColor: '#FFFFFF',
      backgroundColor: DANGER_COLOR,
      duration: 2500
    })
  }
  window.errorSnackbar = errorSnackbar
}
snackbarMessage()


function formatCurrency(number, noOfDecimal, decimalSeparator, thousandSeparator, currencyPosition, currencySymbol) {
  // Convert the number to a string with the desired decimal places
  let formattedNumber = number.toFixed(noOfDecimal)

  // Split the number into integer and decimal parts
  let [integerPart, decimalPart] = formattedNumber.split('.')

  // Add thousand separators to the integer part
  integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator)

  // Set decimalPart to an empty string if it is undefined
  decimalPart = decimalPart || ''

  // Construct the final formatted currency string
  let currencyString = ''

  if (currencyPosition === 'left' || currencyPosition === 'left_with_space') {
    currencyString += currencySymbol
    if (currencyPosition === 'left_with_space') {
      currencyString += ' '
    }
    currencyString += integerPart
    // Add decimal part and decimal separator if applicable
    if (noOfDecimal > 0) {
      currencyString += decimalSeparator + decimalPart
    }
  }

  if (currencyPosition === 'right' || currencyPosition === 'right_with_space') {
    // Add decimal part and decimal separator if applicable
    if (noOfDecimal > 0) {
      currencyString += integerPart + decimalSeparator + decimalPart
    }
    if (currencyPosition === 'right_with_space') {
      currencyString += ' '
    }
    currencyString += currencySymbol
  }

  return currencyString
}

window.formatCurrency = formatCurrency

// Initialize Flatpickr
function InitializeFlatpickr(dateElement) {
  let dateElements = document.querySelectorAll(dateElement);
  if (dateElements) {
    dateElements.forEach(function (datePicker) {
      const flatpickrInstance = flatpickr(datePicker, {
        dateFormat: "Y-m-d",
        minDate: "today",
        altInput: true,
        altFormat: "F j, Y",
        allowInput: true,
        onChange: function (selectedDates, dateStr, instance) {
          fetchAvailableSlots();
        },
      });

      datePicker.addEventListener('click', function () {
        flatpickrInstance.open();
      });
    })
  }
}

InitializeFlatpickr('.date-picker');
InitializeFlatpickr('.calendar-icon');

// flatpickr opened
function flatpickrOpened() {
  let datePickerOpened = document.querySelectorAll('.date-picker-opened');
  datePickerOpened.forEach(function (datePicker) {
    flatpickr(datePicker, {
      inline: true,
      dateFormat: "Y-m-d",
      minDate: "today",
    })
  })
}

document.addEventListener('DOMContentLoaded', function () {
  flatpickrOpened()
})

let showAlert = (button) => {
  Swal.fire({
    title: button?.dataset.swalTitle || 'Alert',
    text: button?.dataset.swalText || '',
    icon: button?.dataset.swalIcon || undefined,
    imageUrl: button?.dataset.swalImage || undefined,
    confirmButtonText: button?.dataset.swalButton || 'OK',
    customClass: {
      confirmButton: 'btn btn-secondary'
    },
    buttonsStyling: false
  });
};

let showAlertBtn = document.getElementById('showAlert');
if (showAlertBtn) {
  showAlertBtn.addEventListener('click', (e) => {
    showAlert(e.currentTarget);
  });
}

let confirmBooking = document.getElementById('confirmBooking');
if (confirmBooking) {
  confirmBooking.addEventListener('click', (e) => {
    const button = e.currentTarget;

    Swal.fire({
      title: button.dataset.swalTitle || 'Confirm Booking',
      text: button.dataset.swalText || '',
      icon: button.dataset.swalIcon || undefined,
      imageUrl: button.dataset.swalImage || undefined,
      html: `
                <div class="form-check d-flex align-items-center justify-content-center gap-2 mb-4 px-4">
                    <input class="form-check-input" type="checkbox" id="confirmCheckbox">
                    <label class="form-check-label" for="confirmCheckbox">
                        I have read the disclaimer and agree upon the
                        <a href="#" class="text-primary text-decoration-underline">Terms and Conditions</a>
                    </label>
                </div>
            `,
      cancelButtonText: button.dataset.swalCancelButton || 'Cancel',
      confirmButtonText: button.dataset.swalButton || 'OK',
      showCancelButton: true,
      customClass: {
        cancelButton: 'btn btn-primary',
        confirmButton: 'btn btn-secondary',
      },
      buttonsStyling: false,
      preConfirm: () => {
        const checkbox = document.getElementById('confirmCheckbox');
        if (!checkbox.checked) {
          Swal.showValidationMessage('You must agree to the Terms and Conditions');
          return false;
        }
      }
    }).then((result) => {
      if (result.isConfirmed) {

        // Trigger the showAlert modal programmatically
        showAlert(showAlertBtn);
      }
    });
  });
}

// Delete Bank Account

let deleteBankAccount = document.getElementById('deleteBankAccount');
if (deleteBankAccount) {
  deleteBankAccount.addEventListener('click', (e) => {
    const button = e.currentTarget;

    Swal.fire({
      title: button.dataset.swalTitle || 'Delete Bank Account',
      text: button.dataset.swalText || '',
      icon: button.dataset.swalIcon || undefined,
      imageUrl: button.dataset.swalImage || undefined,
      cancelButtonText: button.dataset.swalCancelButton || 'Cancel',
      confirmButtonText: button.dataset.swalButton || 'Delete',
      showCancelButton: true,
      customClass: {
        cancelButton: 'btn btn-primary',
        confirmButton: 'btn btn-secondary',
      },
      buttonsStyling: false,
      preConfirm: () => {
        const checkbox = document.getElementById('confirmCheckbox');
        if (!checkbox.checked) {
          Swal.showValidationMessage('You must agree to the Terms and Conditions');
          return false;
        }
      }
    })
  });
}


//   quantiy
const plusBtns = document.querySelectorAll('.iq-quantity-plus')
const minusBtns = document.querySelectorAll('.iq-quantity-minus')
const updateQtyBtn = (elem, value) => {
  const oldValue = elem.closest('[data-qty="btn"]').querySelector('[data-qty="input"]').value
  const newValue = Number(oldValue) + Number(value)
  if (newValue >= 1) {
    elem.closest('[data-qty="btn"]').querySelector('[data-qty="input"]').value = newValue
  }
}
Array.from(plusBtns, (elem) => {
  elem.addEventListener('click', (e) => {
    updateQtyBtn(elem, 1)
  })
})
Array.from(minusBtns, (elem) => {
  elem.addEventListener('click', (e) => {
    updateQtyBtn(elem, -1)
  })
})

// Range Slider

const minRange = document.getElementById('min-range');
const maxRange = document.getElementById('max-range');
const rangeLabel = document.getElementById('rangeValue');
const rangeFill = document.getElementById('range-fill');

function updateRange() {
  if (minRange || maxRange) {
    const min = parseInt(minRange.value);
    const max = parseInt(maxRange.value);

    if (min > max) {
      [minRange.value, maxRange.value] = [max, min]; // swap
    }

    rangeLabel.textContent = `$${Math.min(min, max)} - $${Math.max(min, max)}`;

    const percent1 = (minRange.value / 3000) * 100;
    const percent2 = (maxRange.value / 3000) * 100;

    rangeFill.style.left = percent1 + "%";
    rangeFill.style.width = (percent2 - percent1) + "%";
  }
}

if (minRange || maxRange) {
  minRange.addEventListener("input", updateRange);
  maxRange.addEventListener("input", updateRange);
}

window.addEventListener("DOMContentLoaded", updateRange);
