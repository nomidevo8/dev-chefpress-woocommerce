(function ($) {
  // ─────────────────────────────────────────────────────────
  //  CONSTANTS
  // ─────────────────────────────────────────────────────────
  var ALLERGENS = window.ChefPressOurPlans && window.ChefPressOurPlans.allergens ? window.ChefPressOurPlans.allergens : [
    { name: 'Eggs', icon: '🥚' }, { name: 'Dairy', icon: '🥛' },
    { name: 'Soy', icon: '🌱' }, { name: 'Peanut', icon: '🥜' },
    { name: 'Tree Nuts', icon: '🌰' }, { name: 'Fish', icon: '🐟' },
    { name: 'Shellfish', icon: '🦐' }, { name: 'Sesame', icon: '🥯' },
    { name: 'Wheat', icon: '🌾' }
  ];

  var ACTIVITY_MULTIPLIERS = {
    'Sedentary': 1.2, 'Lightly active': 1.375,
    'Very active': 1.55, 'Highly active': 1.725
  };

  var GOAL_OFFSETS = {
    'Eat healthy': 0, 'Lose Weight': -500,
    'Gain Weight': 400, 'Build Muscle': 300, 'Maintain Weight': 0
  };

  var PLAN_DISCOUNTS = window.ChefPressOurPlans && window.ChefPressOurPlans.planDiscounts ? window.ChefPressOurPlans.planDiscounts : {
    '1 Week': 0, '1 Month': 10, '3 Months': 20, '6 Months': 25
  };

  var PROMO_CODES = window.ChefPressOurPlans && window.ChefPressOurPlans.promoCodes ? window.ChefPressOurPlans.promoCodes : { 'FRESH10': 10 };

  var MEAL_PRICES = window.ChefPressOurPlans && window.ChefPressOurPlans.mealPrices ? window.ChefPressOurPlans.mealPrices : { 'Breakfast': 5, 'Lunch': 12, 'Dinner': 15, 'Snacks': 4 };

  var BMR_FORMULA = window.ChefPressOurPlans && window.ChefPressOurPlans.bmrFormula ? window.ChefPressOurPlans.bmrFormula : 'Mifflin-St Jeor';

  var RECIPES = [
    { id: 'r1', name: 'Grilled Salmon w/ Asparagus', calories: 450, protein: 35, carbs: 10, fats: 25, category: 'Fish', tags: ['Low Carb', 'Express'], image: 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=400&q=80' },
    { id: 'r2', name: 'Lean Beef Stir-fry', calories: 520, protein: 40, carbs: 45, fats: 15, category: 'Meat', tags: ['High Protein'], image: 'https://images.unsplash.com/photo-1512058560366-cd2427ffaa61?auto=format&fit=crop&w=400&q=80' },
    { id: 'r3', name: 'Quinoa Buddha Bowl', calories: 380, protein: 15, carbs: 60, fats: 12, category: 'Veggie', tags: ['Vegetarian', 'Fiber'], image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80' },
    { id: 'r4', name: 'Greek Yogurt Parfait', calories: 280, protein: 20, carbs: 35, fats: 8, category: 'Dairy', tags: ['Breakfast', 'Quick'], image: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=400&q=80' },
    { id: 'r5', name: 'Avocado Toast w/ Egg', calories: 320, protein: 14, carbs: 28, fats: 18, category: 'Veggie', tags: ['Breakfast'], image: 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=400&q=80' },
    { id: 'r6', name: 'Chicken Breast w/ Sweet Potato', calories: 480, protein: 42, carbs: 38, fats: 12, category: 'Meat', tags: ['High Protein', 'Balanced'], image: 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?auto=format&fit=crop&w=400&q=80' },
    { id: 'r7', name: 'Lentil & Spinach Curry', calories: 350, protein: 18, carbs: 55, fats: 8, category: 'Veggie', tags: ['Vegetarian', 'Express'], image: 'https://images.unsplash.com/photo-1542361345-89e58247f2d5?auto=format&fit=crop&w=400&q=80' },
    { id: 'r8', name: 'Baked Cod w/ Lemon', calories: 310, protein: 32, carbs: 8, fats: 14, category: 'Fish', tags: ['Low Carb', 'Light'], image: 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=400&q=80' }
  ];

  var GOAL_DESCS = {
    'Eat healthy': 'Balanced nutrition for daily wellness.',
    'Lose Weight': 'Calorie deficit focused on fat loss.',
    'Gain Weight': 'Healthy surplus for mass building.',
    'Build Muscle': 'High protein focus for hypertrophy.',
    'Maintain Weight': 'Steady macros to keep your current physique.'
  };

  var STEP_LABELS = [
    'Goal Selection', 'Profile Input', 'Target Weight', 'Activity Level',
    'Allergy Check', 'Diet Type', 'Plan Commitment', 'Personalize Box',
    'Menu Selection', 'Summary', 'Starting Day', 'Delivery Slot',
    'Address', 'Payment', 'Success'
  ];

  // ─────────────────────────────────────────────────────────
  //  STATE
  // ─────────────────────────────────────────────────────────
  var d = new Date(new Date().getTime() + (48 * 60 * 60 * 1000));
  var defaultStartDate = d.getFullYear() + '-' +
    String(d.getMonth() + 1).padStart(2, '0') + '-' +
    String(d.getDate()).padStart(2, '0');

  var state = {
    currentStep: 1,
    goal: null,
    weight: 70,
    height: 175,
    age: 25,
    gender: 'male',
    bodyFat: 0,
    targetWeight: 68,
    activityLevel: null,
    hasAllergies: null,
    selectedAllergens: [],
    dietType: null,
    planDuration: null,
    promoCode: '',
    promoDiscount: 0,
    isPromoApplied: false,
    mealQuantities: { 'Breakfast': 0, 'Lunch': 1, 'Dinner': 1, 'Snacks': 1 },
    selectedDays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    menu: {},
    startDate: defaultStartDate,
    deliverySlot: null,
    deliveryInstructions: [],
    address: { type: 'Apartment', building: '', floor: '', flat: '', details: '', lat: null, lng: null },
    isMapFullscreen: false,
    menuFilter: 'All'
  };

  // ─────────────────────────────────────────────────────────
  //  CALCULATIONS
  // ─────────────────────────────────────────────────────────
  function calculateBMR() {
    var w = state.weight;
    var h = state.height;
    var a = state.age;
    var g = state.gender;
    var f = state.bodyFat / 100; // convert to decimal

    if (BMR_FORMULA === 'Mifflin-St Jeor') {
      var base = 10 * w + 6.25 * h - 5 * a;
      return base + (g === 'female' ? -161 : 5);
    } else if (BMR_FORMULA === 'Revised Harris-Benedict') {
      if (g === 'male') {
        return 13.397 * w + 4.799 * h - 5.677 * a + 88.362;
      } else {
        return 9.247 * w + 3.098 * h - 4.330 * a + 447.593;
      }
    } else if (BMR_FORMULA === 'Katch-McArdle') {
      if (f <= 0) {
        alert('Body fat percentage is required for Katch-McArdle formula.');
        return 0;
      }
      return 370 + 21.6 * (1 - f) * w;
    }
    return 0;
  }
  function calculateTDEE() {
    var mult = state.activityLevel ? ACTIVITY_MULTIPLIERS[state.activityLevel] : 1.2;
    return calculateBMR() * mult;
  }
  function calculateDailyTarget() {
    var offset = state.goal ? GOAL_OFFSETS[state.goal] : 0;
    return Math.round(calculateTDEE() + offset);
  }
  function getCalorieRecommendations() {
    var tdee = calculateTDEE();
    var maintenance = Math.round(tdee);
    return {
      maintenance: { cals: maintenance, percent: 100, label: 'Maintain weight', weightLoss: '' },
      mildLoss: { cals: Math.round(tdee - 250), percent: 91, label: 'Mild weight loss', weightLoss: '0.25 kg/week' },
      loss: { cals: Math.round(tdee - 500), percent: 83, label: 'Weight loss', weightLoss: '0.5 kg/week' },
      extremeLoss: { cals: Math.round(tdee - 1000), percent: 66, label: 'Extreme weight loss', weightLoss: '1 kg/week' }
    };
  }
  function calculatePricing() {
    var dailyBase = 0;
    $.each(state.mealQuantities, function(meal, qty) {
      dailyBase += (MEAL_PRICES[meal] || 0) * qty;
    });
    var weeklyBase = dailyBase * state.selectedDays.length;
    var planDiscount = state.planDuration ? PLAN_DISCOUNTS[state.planDuration] : 0;
    var promoDiscount = state.isPromoApplied ? state.promoDiscount : 0;
    var totalDiscountPercent = planDiscount + promoDiscount;
    var totalDiscount = totalDiscountPercent / 100;
    var finalPrice = weeklyBase * (1 - totalDiscount);
    var perDay = finalPrice / (state.selectedDays.length || 1);
    return {
      base: weeklyBase.toFixed(2),
      discount: totalDiscountPercent.toFixed(2),
      final: finalPrice.toFixed(2),
      perDay: perDay.toFixed(2)
    };
  }

  // ─────────────────────────────────────────────────────────
  //  PROGRESS
  // ─────────────────────────────────────────────────────────
  function updateProgress() {
    $('#dev_chefpress_plan_step-number').text(state.currentStep + '/15');
    $('#dev_chefpress_plan_step-label').text(STEP_LABELS[state.currentStep - 1]);
    var circumference = 2 * Math.PI * 20;
    var offset = circumference - (state.currentStep / 15) * circumference;
    $('#dev_chefpress_plan_progress-circle').css('stroke-dashoffset', offset);
  }

  // ────────────────���────────────────────────────────────────
  //  MAP helpers (module-level refs to avoid re-init issues)
  // ─────────────────────────────────────────────────────────
  var leafletMap = null;
  var leafletMarker = null;
  var fullscreenMap = null;
  var fullscreenMarker = null;

  function destroyMaps() {
    if (leafletMap) { leafletMap.remove(); leafletMap = null; leafletMarker = null; }
    if (fullscreenMap) { fullscreenMap.remove(); fullscreenMap = null; fullscreenMarker = null; }
  }

  function initMap(id, isFullscreen) {
    var el = document.getElementById(id);
    if (!el) return;
    var lat = state.address.lat || 25.2048;
    var lng = state.address.lng || 55.2708;
    var map = L.map(id).setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    var marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    function updateCoords(la, ln) {
      state.address.lat = la;
      state.address.lng = ln;
    }
    marker.on('dragend', function(e) {
      var pos = e.target.getLatLng();
      updateCoords(pos.lat, pos.lng);
    });
    map.on('click', function(e) {
      marker.setLatLng([e.latlng.lat, e.latlng.lng]);
      updateCoords(e.latlng.lat, e.latlng.lng);
    });
    if (!state.address.lat && navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(pos) {
        map.setView([pos.coords.latitude, pos.coords.longitude], 15);
        marker.setLatLng([pos.coords.latitude, pos.coords.longitude]);
        updateCoords(pos.coords.latitude, pos.coords.longitude);
      });
    }
    if (isFullscreen) { fullscreenMap = map; fullscreenMarker = marker; }
    else { leafletMap = map; leafletMarker = marker; }
  }

  // ─────────────────────────────────────────────────────────
  //  RENDER ENGINE
  // ─────────────────────────────────────────────────────────
  function renderStep() {
    destroyMaps();
    var container = document.getElementById('dev_chefpress_plan_step-content');
    if (!container) return;

    var wizardContainer = document.getElementById('dev_chefpress_plan_wizard-container');
    if (wizardContainer) {
      wizardContainer.style.display = state.currentStep === 9 ? 'none' : 'block';
    }

    var weeklyMenuContainer = document.getElementById('dev_chefpress_weekly_menu_container');
    if (weeklyMenuContainer) {
      weeklyMenuContainer.style.display = state.currentStep === 9 ? 'block' : 'none';
    }

    container.innerHTML = '';
    updateProgress();

    switch (state.currentStep) {
      case 1:  renderGoalSelection(container); break;
      case 2:  renderProfileInput(container); break;
      case 3:  renderTargetWeight(container); break;
      case 4:  renderActivityLevel(container); break;
      case 5:  renderAllergyCheck(container); break;
      case 6:  renderDietType(container); break;
      case 7:  renderPlanCommitment(container); break;
      case 8:  renderBoxConfig(container); break;
      case 9:  renderMenuSelection(container); break;
      case 10: renderSummary(container); break;
      case 11: renderCalendar(container); break;
      case 12: renderDeliverySlot(container); break;
      case 13: renderAddress(container); break;
      // case 14: renderPayment(container); break;
      // case 15: renderSuccess(container); break;
    }

    updateNavBar();

    if (window.lucide) window.lucide.createIcons();

    // Flatpickr
    if (state.currentStep === 11) {
      var today = new Date();
      var minDate = new Date(today.getTime() + 48 * 3600 * 1000);
      flatpickr('#dev_chefpress_plan_start-date-picker', {
        inline: true,
        minDate: minDate,
        dateFormat: 'Y-m-d',
        defaultDate: state.startDate || minDate,
        onChange: function(selectedDates, dateStr) {
          if (selectedDates.length > 0 && state.startDate !== dateStr) {
            state.startDate = dateStr;
            updateProgress();
            $('#dev_chefpress_plan_step-number').text(state.currentStep + '/15');
          }
        }
      });
    }

    // Leaflet map
    if (state.currentStep === 13) {
      setTimeout(function() {
        initMap('dev_chefpress_plan_delivery-map', false);
        if (state.isMapFullscreen) {
          setTimeout(function() { initMap('dev_chefpress_plan_fullscreen-delivery-map', true); }, 150);
        }
      }, 100);
    }
  }

  function nextStep() {
    if (state.currentStep < 15) { state.currentStep++; renderStep(); }
  }
  function prevStep() {
    if (state.currentStep === 9) {
      var clearBtn = document.querySelector('#cp_weekly_filter_sidebar .cp_weekly_menu_sidebar_clear');
      if (clearBtn) {
        clearBtn.dispatchEvent(new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        }));
      }
    }

    if (state.currentStep > 1) { state.currentStep--; renderStep(); }
  }

  // ─────────────────────────────────────────────────────────
  //  STEP RENDERERS
  // ─────────────────────────────────────────────────────────

  // Step 1 – Goal Selection
  function renderGoalSelection(el) {
    var goals = ['Eat healthy', 'Lose Weight', 'Gain Weight', 'Build Muscle', 'Maintain Weight'];
    var icons = ['apple', 'trending-down', 'trending-up', 'dumbbell', 'activity'];
    var isMobile = window.innerWidth < 640;
    var cards = goals.map(function(g, i) {
      return '<div onclick="setGoal(\'' + g + '\')" class="dev_chefpress_plan_card-selectable ' + (state.goal === g ? 'dev_chefpress_plan_active' : '') + ' dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-4 dev_chefpress_plan_p-4" style="' + (isMobile ? 'flex-direction:column;text-align:center;padding:1rem!important;' : '') + '">' +
        '<div style="width:3rem!important;height:3rem!important;background:var(--emerald-100)!important;border-radius:0.75rem!important;display:flex!important;align-items:center!important;justify-content:center!important;color:var(--emerald-600)!important;flex-shrink:0!important;">' +
          '<i data-lucide="' + icons[i] + '" style="width:1.25rem!important;height:1.25rem!important;"></i>' +
        '</div>' +
        '<div class="dev_chefpress_plan_text-left" style="' + (isMobile ? 'text-align:center!important;' : '') + '">' +
          '<h3 class="dev_chefpress_plan_font-bold dev_chefpress_plan_text-gray-900" style="font-size:' + (isMobile ? '1rem' : '1.25rem') + '!important;margin:0!important;">' + g + '</h3>' +
          '<p style="font-size:' + (isMobile ? '0.69rem' : '0.75rem') + '!important;color:var(--gray-500)!important;margin:0.25rem 0 0 0!important;line-height:1.4!important;">' + GOAL_DESCS[g] + '</p>' +
        '</div>' +
      '</div>';
    }).join('');

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">What\'s your primary goal?</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="max-width:28rem!important;margin:0 auto!important;font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">We\'ll tailor your nutrition plan based on your objective.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-4 dev_chefpress_plan_mb-8" style="grid-template-columns:repeat(1,minmax(0,1fr))!important;">' + cards + '</div>';

    // responsive
    if (window.innerWidth >= 640) {
      el.querySelector('.dev_chefpress_plan_grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
    }
  }

  // Step 2 – Profile Input
  function renderProfileInput(el) {
    var isMobile = window.innerWidth < 640;
    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">Tell us about yourself</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">This helps us calculate your metabolic rate accurately.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_space-y-6 dev_chefpress_plan_mb-8" style="max-width:28rem!important;margin:0 auto!important;">' +
        '<div>' +
          '<label class="dev_chefpress_plan_font-bold dev_chefpress_plan_text-gray-700" style="display:block!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;margin-bottom:0.5rem!important;">Current Weight (kg)</label>' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-4" style="gap:' + (isMobile ? '0.5rem' : '1rem') + '!important;">' +
            '<button onclick="updateWeight(-1)" class="dev_chefpress_plan_spinBtn" style="width:' + (isMobile ? '2rem' : '2.5rem') + '!important;height:' + (isMobile ? '2rem' : '2.5rem') + '!important;"><i data-lucide="minus" style="width:1rem!important;height:1rem!important;"></i></button>' +
            '<input type="number" id="dev_chefpress_plan_weight-input" value="' + state.weight + '" class="dev_chefpress_plan_input-field dev_chefpress_plan_profile-number-input" min="30" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">' +
            '<button onclick="updateWeight(1)" class="dev_chefpress_plan_spinBtn" style="width:' + (isMobile ? '2rem' : '2.5rem') + '!important;height:' + (isMobile ? '2rem' : '2.5rem') + '!important;"><i data-lucide="plus" style="width:1rem!important;height:1rem!important;"></i></button>' +
          '</div>' +
        '</div>' +
        '<div>' +
          '<label class="dev_chefpress_plan_font-bold dev_chefpress_plan_text-gray-700" style="display:block!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;margin-bottom:0.5rem!important;">Height (cm)</label>' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-4" style="gap:' + (isMobile ? '0.5rem' : '1rem') + '!important;">' +
            '<button onclick="updateHeight(-1)" class="dev_chefpress_plan_spinBtn" style="width:' + (isMobile ? '2rem' : '2.5rem') + '!important;height:' + (isMobile ? '2rem' : '2.5rem') + '!important;"><i data-lucide="minus" style="width:1rem!important;height:1rem!important;"></i></button>' +
            '<input type="number" id="dev_chefpress_plan_height-input" value="' + state.height + '" class="dev_chefpress_plan_input-field dev_chefpress_plan_profile-number-input" min="100" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">' +
            '<button onclick="updateHeight(1)" class="dev_chefpress_plan_spinBtn" style="width:' + (isMobile ? '2rem' : '2.5rem') + '!important;height:' + (isMobile ? '2rem' : '2.5rem') + '!important;"><i data-lucide="plus" style="width:1rem!important;height:1rem!important;"></i></button>' +
          '</div>' +
        '</div>' +
        '<div>' +
          '<label class="dev_chefpress_plan_font-bold dev_chefpress_plan_text-gray-700" style="display:block!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;margin-bottom:0.5rem!important;">Age</label>' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-4" style="gap:' + (isMobile ? '0.5rem' : '1rem') + '!important;">' +
            '<button onclick="updateAge(-1)" class="dev_chefpress_plan_spinBtn" style="width:' + (isMobile ? '2rem' : '2.5rem') + '!important;height:' + (isMobile ? '2rem' : '2.5rem') + '!important;"><i data-lucide="minus" style="width:1rem!important;height:1rem!important;"></i></button>' +
            '<input type="number" id="dev_chefpress_plan_age-input" value="' + state.age + '" class="dev_chefpress_plan_input-field dev_chefpress_plan_profile-number-input" min="10" max="120" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">' +
            '<button onclick="updateAge(1)" class="dev_chefpress_plan_spinBtn" style="width:' + (isMobile ? '2rem' : '2.5rem') + '!important;height:' + (isMobile ? '2rem' : '2.5rem') + '!important;"><i data-lucide="plus" style="width:1rem!important;height:1rem!important;"></i></button>' +
          '</div>' +
        '</div>' +
        '<div>' +
          '<label class="dev_chefpress_plan_font-bold dev_chefpress_plan_text-gray-700" style="display:block!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;margin-bottom:0.5rem!important;">Gender</label>' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_gap-6" style="gap:' + (isMobile ? '1rem' : '1.5rem') + '!important;">' +
            '<label class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-2" style="font-size:' + (isMobile ? '0.875rem' : '0.9375rem') + '!important;">' +
              '<input type="radio" name="gender" value="male" ' + (state.gender === 'male' ? 'checked' : '') + ' class="dev_chefpress_plan_radio-input">' +
              '<span>Male</span>' +
            '</label>' +
            '<label class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-2" style="font-size:' + (isMobile ? '0.875rem' : '0.9375rem') + '!important;">' +
              '<input type="radio" name="gender" value="female" ' + (state.gender === 'female' ? 'checked' : '') + ' class="dev_chefpress_plan_radio-input">' +
              '<span>Female</span>' +
            '</label>' +
          '</div>' +
        '</div>' +
        '<div>' +
          '<label class="dev_chefpress_plan_font-bold dev_chefpress_plan_text-gray-700" style="display:block!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;margin-bottom:0.5rem!important;">Body Fat % (optional)</label>' +
          '<input type="number" id="dev_chefpress_plan_bodyfat-input" value="' + state.bodyFat + '" class="dev_chefpress_plan_input-field" min="0" max="50" step="0.1" placeholder="e.g. 15.5" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">' +
        '</div>' +
      '</div>';

    // spin-button style
    $(el).find('.dev_chefpress_plan_spinBtn').css({
      width:'2.5rem !important', height:'2.5rem !important', borderRadius:'9999px !important', display:'flex !important',
      alignItems:'center !important', justifyContent:'center !important',
      color:'var(--emerald-600) !important', background:'transparent !important',
      transition:'background 0.2s !important', cursor:'pointer !important', flexShrink:'0 !important'
    }).hover(function(){ $(this).css('background','var(--emerald-50) !important'); },
              function(){ $(this).css('background','transparent !important'); });

    $('#dev_chefpress_plan_weight-input').on('change', function() { state.weight = Math.max(30, Number($(this).val())); });
    $('#dev_chefpress_plan_height-input').on('change', function() { state.height = Math.max(100, Number($(this).val())); });
    $('#dev_chefpress_plan_age-input').on('change', function() { state.age = Math.max(10, Math.min(120, Number($(this).val()))); });
    $('#dev_chefpress_plan_bodyfat-input').on('change', function() { state.bodyFat = Math.max(0, Math.min(50, Number($(this).val()) || 0)); });
    $('input[name="gender"]').on('change', function() { state.gender = $(this).val(); });
  }

  // Step 3 – Target Weight
  function renderTargetWeight(el) {
    var isMobile = window.innerWidth < 640;
    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">Set your target</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">What\'s your ideal weight goal?</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_mb-8" style="max-width:28rem!important;margin:0 auto!important;">' +
        '<div style="background:var(--emerald-50)!important;padding:' + (isMobile ? '1rem' : '1.5rem') + '!important;border-radius:1.5rem!important;text-align:center!important;margin-bottom:1.5rem!important;">' +
          '<span style="font-size:0.75rem!important;font-weight:700!important;color:var(--emerald-600)!important;text-transform:uppercase!important;letter-spacing:0.1em!important;">Target Weight</span>' +
          '<div style="font-size:' + (isMobile ? '2.5rem' : '3.75rem') + '!important;font-weight:900!important;color:var(--emerald-900)!important;margin:0.5rem 0!important;" id="dev_chefpress_plan_target-display">' +
            state.targetWeight + '<span style="font-size:' + (isMobile ? '1rem' : '1.5rem') + '!important;font-weight:500!important;color:var(--emerald-500)!important;margin-left:0.25rem!important;">kg</span>' +
          '</div>' +
          '<input type="range" min="40" max="150" value="' + state.targetWeight + '" id="dev_chefpress_plan_target-range" class="dev_chefpress_plan_range-slider" style="width:100%!important;">' +
        '</div>' +
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between" style="font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;font-weight:500!important;color:var(--gray-400)!important;gap:0.5rem!important;">' +
          '<span style="min-width:fit-content!important;">Current: ' + state.weight + 'kg</span>' +
          '<span style="color:var(--emerald-600)!important;font-weight:700!important;" id="dev_chefpress_plan_diff-label">Diff: ' + Math.abs(state.targetWeight - state.weight) + 'kg</span>' +
        '</div>' +
      '</div>';

    $('#dev_chefpress_plan_target-range').on('input', function() {
      state.targetWeight = Number($(this).val());
      $('#dev_chefpress_plan_target-display').html(state.targetWeight + '<span style="font-size:' + (isMobile ? '1rem' : '1.5rem') + '!important;font-weight:500!important;color:var(--emerald-500)!important;margin-left:0.25rem!important;">kg</span>');
      $('#dev_chefpress_plan_diff-label').text('Diff: ' + Math.abs(state.targetWeight - state.weight) + 'kg');
    });
  }

  // Step 4 – Activity Level
  function renderActivityLevel(el) {
    var isMobile = window.innerWidth < 640;
    var levels = ['Sedentary', 'Lightly active', 'Very active', 'Highly active'];
    var descs = [
      'Little to no exercise, desk job',
      '1-2 light workouts a week',
      '3-5 moderate workouts a week',
      '6-7 intense workouts a week'
    ];
    var cards = levels.map(function(l, i) {
      var isActive = state.activityLevel === l;
      return '<div onclick="setActivity(\'' + l + '\')" class="dev_chefpress_plan_card-selectable ' + (isActive ? 'dev_chefpress_plan_active' : '') + ' dev_chefpress_plan_flex ' + (isMobile ? 'dev_chefpress_plan_flex-col dev_chefpress_plan_items-center dev_chefpress_plan_text-center' : 'dev_chefpress_plan_items-center dev_chefpress_plan_justify-between') + ' dev_chefpress_plan_p-4" style="' + (isMobile ? 'padding:1rem!important;' : '') + '">' +
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-4' + (isMobile ? ' dev_chefpress_plan_flex-col' : '') + '">' +
          '<div style="width:2.5rem!important;height:2.5rem!important;background:var(--emerald-100)!important;border-radius:0.5rem!important;display:flex!important;align-items:center!important;justify-content:center!important;color:var(--emerald-600)!important;flex-shrink:0!important;">' +
            '<i data-lucide="zap" style="width:1rem!important;height:1rem!important;"></i>' +
          '</div>' +
          '<div class="dev_chefpress_plan_text-left" style="' + (isMobile ? 'text-align:center!important;' : '') + '">' +
            '<h3 style="font-weight:700!important;color:var(--gray-900)!important;font-size:' + (isMobile ? '0.9375rem' : '1.25rem') + '!important;margin:0!important;">' + l + '</h3>' +
            '<p style="font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;color:var(--gray-500)!important;margin:0.25rem 0 0 0!important;line-height:1.3!important;">' + descs[i] + '</p>' +
          '</div>' +
        '</div>' +
        (isActive ? '<i data-lucide="check-circle-2" style="color:var(--emerald-500)!important;width:1.25rem!important;height:1.25rem!important;flex-shrink:0!important;margin-top:' + (isMobile ? '0.5rem' : '0') + '!important;"></i>' : '') +
      '</div>';
    }).join('');

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">Activity Level</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">How active is your daily lifestyle?</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-4 dev_chefpress_plan_mb-8" style="grid-template-columns:repeat(1,minmax(0,1fr))!important;">' + cards + '</div>';
    if (window.innerWidth >= 768) el.querySelector('.dev_chefpress_plan_grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
  }

  // Step 5 – Allergy Check
  function renderAllergyCheck(el) {
    var isMobile = window.innerWidth < 640;
    var inner;
    if (state.hasAllergies !== true) {
      inner =
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_flex-wrap dev_chefpress_plan_gap-4 dev_chefpress_plan_mb-8" style="max-width:28rem!important;margin:0 auto!important;">' +
          '<button onclick="setHasAllergies(true)" class="dev_chefpress_plan_btn-outline dev_chefpress_plan_flex-1 dev_chefpress_plan_py-6 dev_chefpress_plan_flex dev_chefpress_plan_flex-col dev_chefpress_plan_items-center dev_chefpress_plan_gap-4" style="padding:' + (isMobile ? '1rem' : '1.5rem') + '!important;font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">' +
            '<i data-lucide="alert-circle" style="width:' + (isMobile ? '1.5rem' : '2rem') + '!important;height:' + (isMobile ? '1.5rem' : '2rem') + '!important;"></i>Yes, I have allergies' +
          '</button>' +
          '<button onclick="setHasAllergies(false)" class="dev_chefpress_plan_flex-1 ' + (state.hasAllergies === false ? 'dev_chefpress_plan_btn-primary' : 'dev_chefpress_plan_btn-outline') + ' dev_chefpress_plan_py-6 dev_chefpress_plan_flex dev_chefpress_plan_flex-col dev_chefpress_plan_items-center dev_chefpress_plan_gap-4" style="padding:' + (isMobile ? '1rem' : '1.5rem') + '!important;font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">' +
            '<i data-lucide="check-circle" style="width:' + (isMobile ? '1.5rem' : '2rem') + '!important;height:' + (isMobile ? '1.5rem' : '2rem') + '!important;"></i>No, I\'m good' +
          '</button>' +
        '</div>';
    } else {
      var chips = ALLERGENS.map(function(a) {
        var sel = state.selectedAllergens.indexOf(a.name) !== -1;
        return '<div onclick="toggleAllergen(\'' + a.name + '\')" class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-2 dev_chefpress_plan_cursor-pointer dev_chefpress_plan_font-bold" style="padding:' + (isMobile ? '0.5rem 1rem' : '0.5rem 1.5rem') + '!important;border-radius:9999px!important;border:2px solid ' + (sel ? 'var(--emerald-500)' : 'var(--gray-100)') + '!important;background:' + (sel ? 'var(--emerald-50)' : '#fff') + '!important;color:' + (sel ? 'var(--emerald-700)' : 'var(--gray-400)') + '!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;transition:all 0.2s!important;box-shadow:' + (sel ? '0 0 0 4px rgba(16,185,129,0.1)' : 'none') + '!important;">' +
          '<span>' + a.name + '</span>' +
        '</div>';
      }).join('');
      inner = '<div class="dev_chefpress_plan_flex dev_chefpress_plan_flex-wrap dev_chefpress_plan_justify-center dev_chefpress_plan_gap-3 dev_chefpress_plan_mb-8" style="max-width:42rem!important;margin:0 auto!important;">' + chips + '</div>';
    }

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">Any Allergies?</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">We\'ll exclude these from your menu options.</p>' +
      '</div>' +
      inner;
  }

  // Step 6 – Diet Type
  function renderDietType(el) {
    var isMobile = window.innerWidth < 640;
    var diets = [
      { name: 'High Protein', desc: 'Boosts muscle strength and vitality with lean proteins', icon: '🍗', recommended: true, macros: { p: '40-50%', c: '35-40%', f: '10-25%' }, widths: { p: '45%', c: '35%', f: '20%' } },
      { name: 'Balanced', desc: 'Provides the nutrients your body needs to thrive', icon: '⚖️', recommended: false, macros: { p: '20-35%', c: '40-55%', f: '20-30%' }, widths: { p: '25%', c: '45%', f: '30%' } },
      { name: 'Low-Carb', desc: 'Focuses on healthy fats and proteins while reducing sugars', icon: '🥑', recommended: false, macros: { p: '25-30%', c: '10-15%', f: '55-65%' }, widths: { p: '30%', c: '10%', f: '60%' } },
      { name: 'Vegetarian', desc: 'Plant-based nutrition rich in fiber and antioxidants', icon: '🥗', recommended: false, macros: { p: '15-20%', c: '50-60%', f: '25-30%' }, widths: { p: '20%', c: '55%', f: '25%' } }
    ];

    var cards = diets.map(function(d) {
      var isActive = state.dietType === d.name;
      return '<div onclick="setDiet(\'' + d.name + '\')" class="dev_chefpress_plan_card-selectable ' + (isActive ? 'dev_chefpress_plan_active' : '') + ' dev_chefpress_plan_flex dev_chefpress_plan_flex-col dev_chefpress_plan_text-left" style="padding:' + (isMobile ? '1rem' : '1.5rem') + '!important;position:relative!important;">' +
        (d.recommended ? '<div style="position:absolute!important;top:' + (isMobile ? '0.5rem' : '1rem') + '!important;left:' + (isMobile ? '0.5rem' : '1rem') + '!important;background:var(--emerald-500)!important;color:#fff!important;font-size:' + (isMobile ? '0.5rem' : '0.625rem') + '!important;font-weight:900!important;padding:0.25rem 0.5rem!important;border-radius:0.25rem!important;text-transform:uppercase!important;letter-spacing:0.05em!important;">BEST</div>' : '') +
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between dev_chefpress_plan_items-start" style="' + (d.recommended ? 'margin-top:' + (isMobile ? '1.25rem' : '2rem') + ' !important;' : '') + '">' +
          '<div style="flex:1!important;">' +
            '<h3 style="font-size:' + (isMobile ? '1rem' : '1.25rem') + '!important;font-weight:900!important;color:var(--emerald-900)!important;margin-bottom:0.25rem!important;">' + d.name + '</h3>' +
            '<p style="font-size:' + (isMobile ? '0.6875rem' : '0.6875rem') + '!important;color:var(--gray-400)!important;font-weight:500!important;line-height:1.5!important;margin-bottom:0.75rem!important;">' + d.desc + '</p>' +
          '</div>' +
          '<span style="font-size:' + (isMobile ? '1.25rem' : '1.5rem') + '!important;margin-left:0.5rem!important;flex-shrink:0!important;">' + d.icon + '</span>' +
        '</div>' +
        '<div style="margin-top:auto!important;">' +
          '<div class="dev_chefpress_plan_diet-macro-bar" style="margin-bottom:0.75rem!important;height:0.5rem!important;border-radius:9999px!important;display:flex!important;overflow:hidden!important;background:var(--gray-100)!important;">' +
            '<div style="background:#c084fc!important;width:' + d.widths.p + '!important;"></div>' +
            '<div style="background:#fb923c!important;width:' + d.widths.c + '!important;"></div>' +
            '<div style="background:#60a5fa!important;width:' + d.widths.f + '!important;"></div>' +
          '</div>' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between" style="font-size:' + (isMobile ? '0.5625rem' : '0.625rem') + '!important;font-weight:700!important;color:var(--gray-400)!important;text-transform:uppercase!important;letter-spacing:-0.05em!important;">' +
            '<span>' + d.macros.p + ' P</span><span>' + d.macros.c + ' C</span><span>' + d.macros.f + ' F</span>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">Diet Preference</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">Choose a macro distribution that fits your lifestyle.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-6 dev_chefpress_plan_mb-8" style="grid-template-columns:repeat(1,minmax(0,1fr))!important;">' + cards + '</div>';
    if (window.innerWidth >= 768) el.querySelector('.dev_chefpress_plan_grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
  }

  // Step 7 – Plan Commitment
  function renderPlanCommitment(el) {
    var isMobile = window.innerWidth < 640;
    var plans = ['1 Week', '1 Month', '3 Months', '6 Months'];
    var planDetails = {
      '1 Week':  { billing: 'Billed every week',        weeks: '1 Week',  discount: 'Standard Rate' },
      '1 Month': { billing: 'Billed every month',       weeks: '4 Weeks', discount: 'Save ' + PLAN_DISCOUNTS['1 Month'] + '% Overall' },
      '3 Months':{ billing: 'Billed every 3 months',    weeks: '12 Weeks',discount: 'Save ' + PLAN_DISCOUNTS['3 Months'] + '% Overall' },
      '6 Months':{ billing: 'Billed every 6 months',    weeks: '24 Weeks',discount: 'Save ' + PLAN_DISCOUNTS['6 Months'] + '% Overall' }
    };

    var planCards = plans.map(function(p) {
      var orig = state.planDuration;
      state.planDuration = p;
      var pricing = calculatePricing();
      state.planDuration = orig;
      var det = planDetails[p];
      var isActive = state.planDuration === p;
      var totalMeals = 0;
      $.each(state.mealQuantities, function(_, q){ totalMeals += q; });

      return '<div onclick="setPlan(\'' + p + '\')" class="dev_chefpress_plan_card-selectable ' + (isActive ? 'dev_chefpress_plan_active' : '') + ' dev_chefpress_plan_flex dev_chefpress_plan_flex-col dev_chefpress_plan_p-6 dev_chefpress_plan_text-center" style="height:100%!important;padding:' + (isMobile ? '1rem' : '1.5rem') + '!important;">' +
        '<div class="">' +
          '<h3 style="font-size:' + (isMobile ? '1rem' : '1.25rem') + '!important;font-weight:900!important;color:var(--emerald-900)!important;margin:0!important;">' + p + '</h3>' +
          '<p style="font-size:0.625rem!important;font-weight:700!important;color:var(--gray-400)!important;text-transform:uppercase!important;letter-spacing:0.05em!important;margin:0.25rem 0 0 0!important;">' + det.billing + '</p>' +
        '</div>' +
        '<div class="dev_chefpress_plan_my-4" style="margin:' + (isMobile ? '0.75rem 0' : '1rem 0') + '!important;">' +
          '<p style="font-size:' + (isMobile ? '1.25rem' : '1.5rem') + '!important;font-weight:900!important;color:var(--emerald-900)!important;margin:0!important;">AED ' + pricing.perDay + '</p>' +
          '<p style="font-size:0.625rem!important;font-weight:700!important;color:var(--gray-400)!important;text-transform:uppercase!important;letter-spacing:0.1em!important;margin:0.25rem 0 0 0!important;">PER DAY</p>' +
        '</div>' +
        '<div style="background:rgba(236,253,245,0.5)!important;border-radius:1rem!important;padding:' + (isMobile ? '0.75rem' : '1rem') + '!important;margin-bottom:' + (isMobile ? '1rem' : '1.5rem') + '!important;text-align:left!important;">' +
          '<p style="font-size:0.5625rem!important;font-weight:900!important;color:rgba(6,95,70,0.4)!important;text-transform:uppercase!important;letter-spacing:0.1em!important;margin-bottom:0.5rem!important;">INCLUDES:</p>' +
          '<div class="dev_chefpress_plan_space-y-1">' +
            '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-2" style="font-size:' + (isMobile ? '0.65rem' : '0.6875rem') + '!important;font-weight:700!important;color:var(--emerald-900)!important;">' +
              '<i data-lucide="check" style="width:0.75rem!important;height:0.75rem!important;color:var(--emerald-500)!important;"></i>' +
              '<span>' + state.selectedDays.length + ' Days / Week</span>' +
            '</div>' +
            '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-2" style="font-size:' + (isMobile ? '0.65rem' : '0.6875rem') + '!important;font-weight:700!important;color:var(--emerald-900)!important;">' +
              '<i data-lucide="check" style="width:0.75rem!important;height:0.75rem!important;color:var(--emerald-500)!important;"></i>' +
              '<span>' + totalMeals + ' Meals / Day</span>' +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="dev_chefpress_plan_mb-6 dev_chefpress_plan_pt-4" style="border-top:1px solid var(--gray-100)!important;margin-top:auto!important;">' +
          '<p style="font-size:' + (isMobile ? '1rem' : '1.125rem') + '!important;font-weight:900!important;color:var(--emerald-900)!important;margin:0.5rem 0 0 0!important;">' + det.weeks + '</p>' +
          '<p style="font-size:0.625rem!important;font-weight:700!important;color:var(--gray-400)!important;text-transform:uppercase!important;letter-spacing:0.1em!important;margin:0.25rem 0 0 0!important;">DURATION</p>' +
        '</div>' +
        '<div>' +
          '<div style="display:inline-block!important;padding:0.5rem 1rem!important;border-radius:9999px!important;background:rgba(209,250,229,0.5)!important;color:var(--emerald-700)!important;font-size:0.625rem!important;font-weight:900!important;text-transform:uppercase!important;letter-spacing:0.05em!important;">' +
            det.discount +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-10">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">Choose your plan</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">Commit longer to unlock premium discounts.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_mb-8" style="max-width:28rem!important;margin:0 auto!important;">' +
        '<div style="background:#fff!important;border:1px solid var(--gray-100)!important;border-radius:1.5rem!important;padding:' + (isMobile ? '0.75rem' : '1rem') + '!important;box-shadow:0 1px 3px rgba(0,0,0,0.05)!important;">' +
          '<p style="font-size:' + (isMobile ? '0.7125rem' : '0.75rem') + '!important;font-weight:700!important;color:var(--emerald-900)!important;margin-bottom:0.5rem!important;text-align:center!important;">Have a promo code?</p>' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_gap-3" style="gap:' + (isMobile ? '0.5rem' : '0.75rem') + '!important;">' +
            '<div class="dev_chefpress_plan_relative dev_chefpress_plan_flex-grow">' +
              '<input type="text" id="dev_chefpress_plan_promo-input" value="' + state.promoCode + '" placeholder="CODE" style="width:100%!important;padding:' + (isMobile ? '0.5rem 3.5rem 0.5rem 1rem' : '0.75rem 1.5rem') + '!important;border-radius:1rem!important;border:1px solid var(--gray-100)!important;outline:none!important;font-weight:700!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;text-transform:uppercase!important;background:#fff!important;font-family:inherit!important;" ' + (state.isPromoApplied ? 'disabled' : '') + '>' +
              (state.isPromoApplied ? '<i data-lucide="check-circle-2" style="position:absolute!important;right:1rem!important;top:50%!important;transform:translateY(-50%)!important;color:var(--emerald-500)!important;width:1rem!important;height:1rem!important;"></i>' : '') +
            '</div>' +
            (state.isPromoApplied ?
              '<button onclick="removePromo()" style="padding:' + (isMobile ? '0.5rem 0.75rem' : '0.75rem 1.5rem') + '!important;background:var(--red-50)!important;color:var(--red-600)!important;font-weight:900!important;border-radius:1rem!important;border:none!important;cursor:pointer!important;font-size:' + (isMobile ? '0.75rem' : '0.875rem') + '!important;transition:background 0.2s!important;">Remove</button>' :
              '<button onclick="applyPromo()" style="padding:' + (isMobile ? '0.5rem 0.75rem' : '0.75rem 1.5rem') + '!important;background:var(--emerald-500)!important;color:#fff!important;font-weight:900!important;border-radius:1rem!important;border:none!important;cursor:pointer!important;font-size:' + (isMobile ? '0.75rem' : '0.875rem') + '!important;box-shadow:0 4px 14px rgba(16,185,129,0.2)!important;transition:background 0.2s!important;">Apply</button>'
            ) +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-6 dev_chefpress_plan_mb-10" style="grid-template-columns:repeat(1,minmax(0,1fr))!important; margin-top:2rem!important;">' + planCards + '</div>';

    if (window.innerWidth >= 768) el.querySelector('.dev_chefpress_plan_grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
    if (window.innerWidth >= 1024) el.querySelector('.dev_chefpress_plan_grid').style.gridTemplateColumns = 'repeat(4,minmax(0,1fr))';

    $('#dev_chefpress_plan_promo-input').on('input', function() { state.promoCode = $(this).val().toUpperCase(); });
  }

  // Step 8 – Box Config
  function renderBoxConfig(el) {
    var isMobile = window.innerWidth < 640;
    var mealTypes = [
      { name: 'Breakfast', icon: '🍳', price: MEAL_PRICES['Breakfast'] || 5 },
      { name: 'Lunch',     icon: '🥗', price: MEAL_PRICES['Lunch'] || 12 },
      { name: 'Dinner',    icon: '🥩', price: MEAL_PRICES['Dinner'] || 15 },
      { name: 'Snacks',    icon: '🍎', price: MEAL_PRICES['Snacks'] || 4 }
    ];
    var daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var target = calculateDailyTarget();
    var pricing = calculatePricing();
    var totalMealsPerDay = 0;
    $.each(state.mealQuantities, function(_, q){ totalMealsPerDay += q; });

    var mealCards = mealTypes.map(function(m) {
      return '<div style="background:#fff!important;border:1px solid var(--gray-100)!important;border-radius:1.5rem!important;padding:' + (isMobile ? '0.75rem' : '1rem') + '!important;display:flex!important;align-items:center!important;justify-content:space-between!important;box-shadow:0 1px 3px rgba(0,0,0,0.05)!important;transition:box-shadow 0.2s!important;gap:' + (isMobile ? '0.5rem' : '1rem') + '!important;" style="">' +
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-4" style="gap:' + (isMobile ? '0.75rem' : '1rem') + '!important;min-width:0!important;">' +
          '<div style="width:' + (isMobile ? '2.5rem' : '3rem') + '!important;height:' + (isMobile ? '2.5rem' : '3rem') + '!important;background:var(--emerald-50)!important;border-radius:1rem!important;display:flex!important;align-items:center!important;justify-content:center!important;font-size:' + (isMobile ? '1rem' : '1.25rem') + '!important;flex-shrink:0!important;">' + m.icon + '</div>' +
          '<div style="min-width:0!important;">' +
            '<h3 style="font-weight:900!important;color:var(--emerald-900)!important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + '!important;margin:0!important;">' + m.name + '</h3>' +
            '<p style="font-size:' + (isMobile ? '0.6375rem' : '0.625rem') + ' !important;font-weight:700 !important;color:var(--gray-400) !important;margin:0.25rem 0 0 0!important;">AED ' + m.price + '/meal</p>' +
          '</div>' +
        '</div>' +
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-4" style="gap:' + (isMobile ? '0.25rem' : '1rem') + '!important;flex-shrink:0!important;">' +
          '<button onclick="updateMealQuantity(\'' + m.name + '\',-1)" style="width:' + (isMobile ? '1.75rem' : '2rem') + ' !important;height:' + (isMobile ? '1.75rem' : '2rem') + ' !important;border-radius:9999px !important;border:1px solid var(--gray-100) !important;display:flex !important;align-items:center !important;justify-content:center !important;color:var(--gray-400) !important;background:transparent !important;cursor:pointer !important;transition:all 0.2s !important;font-size:' + (isMobile ? '0.875rem' : '1rem') + ' !important;font-weight:700 !important;">-</button>' +
          '<span style="width:1rem !important;text-align:center !important;font-weight:900 !important;color:var(--emerald-900) !important;font-size:' + (isMobile ? '0.8125rem' : '0.875rem') + ' !important;flex-shrink:0!important;">' + (state.mealQuantities[m.name] || 0) + '</span>' +
          '<button onclick="updateMealQuantity(\'' + m.name + '\',1)" style="width:' + (isMobile ? '1.75rem' : '2rem') + ' !important;height:' + (isMobile ? '1.75rem' : '2rem') + ' !important;border-radius:9999px !important;border:1px solid var(--gray-100) !important;display:flex !important;align-items:center !important;justify-content:center !important;color:var(--gray-400) !important;background:transparent !important;cursor:pointer !important;transition:all 0.2s !important;font-size:' + (isMobile ? '0.875rem' : '1rem') + ' !important;font-weight:700 !important;">+</button>' +
        '</div>' +
      '</div>';
    }).join('');

    var dayBtns = daysOfWeek.map(function(day) {
      var sel = state.selectedDays.indexOf(day) !== -1;
      return '<button onclick="toggleDay(\'' + day + '\')" style="padding:' + (isMobile ? '0.5rem 0.75rem' : '0.75rem 1.25rem') + ' !important;border-radius:1rem !important;border:2px solid ' + (sel ? 'var(--emerald-500)' : 'var(--gray-100)') + ' !important;background:' + (sel ? 'var(--emerald-500)' : '#fff') + ' !important;color:' + (sel ? '#fff' : 'var(--gray-400)') + ' !important;font-weight:900 !important;font-size:' + (isMobile ? '0.7rem' : '0.75rem') + ' !important;min-width:' + (isMobile ? '56px' : '70px') + ' !important;transition:all 0.2s !important;box-shadow:' + (sel ? '0 4px 14px rgba(16,185,129,0.2)' : 'none') + ' !important;cursor:pointer !important;">' + day + '</button>';
    }).join('');

    var kcalPerMeal = totalMealsPerDay > 0 ? Math.round(target / totalMealsPerDay) : 0;

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4" style="font-size:' + (isMobile ? '1.875rem' : '2.25rem') + '!important;">Personalize your box</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500" style="font-size:' + (isMobile ? '0.875rem' : '1rem') + '!important;">Select your meals and delivery frequency.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-8 dev_chefpress_plan_mb-8" id="dev_chefpress_plan_box-grid" style="grid-template-columns:repeat(1,minmax(0,1fr)) !important;">' +
        '<div style="grid-column:span 1 !important;" id="dev_chefpress_plan_box-left">' +
          '<div class="dev_chefpress_plan_mb-8">' +
            '<p style="font-size:' + (isMobile ? '0.6375rem' : '0.6875rem') + ' !important;font-weight:900 !important;color:var(--gray-400) !important;text-transform:uppercase !important;letter-spacing:0.2em !important;margin-bottom:' + (isMobile ? '0.5rem' : '1rem') + ' !important;">MEALS/DAY</p>' +
            '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-4" style="grid-template-columns:repeat(1,minmax(0,1fr)) !important;" id="dev_chefpress_plan_meal-cards">' + mealCards + '</div>' +
          '</div>' +
          '<div>' +
            '<p style="font-size:' + (isMobile ? '0.6375rem' : '0.6875rem') + ' !important;font-weight:900 !important;color:var(--gray-400) !important;text-transform:uppercase !important;letter-spacing:0.2em !important;margin-bottom:' + (isMobile ? '0.5rem' : '1rem') + ' !important;">DAYS/WEEK</p>' +
            '<div class="dev_chefpress_plan_flex dev_chefpress_plan_flex-wrap dev_chefpress_plan_gap-2" style="gap:' + (isMobile ? '0.5rem' : '0.75rem') + '!important;">' + dayBtns + '</div>' +
          '</div>' +
        '</div>' +
        '<div id="dev_chefpress_plan_box-right">' +
          '<div style="background:var(--emerald-900) !important;padding:' + (isMobile ? '1rem' : '1.5rem') + ' !important;border-radius:2rem !important;color:#fff !important;box-shadow:0 20px 25px -5px rgba(6,78,59,0.1) !important;" class="dev_chefpress_plan_mb-4">' +
            '<div class="dev_chefpress_plan_mb-6">' +
              '<p style="font-size:0.625rem !important;font-weight:700 !important;color:var(--emerald-300) !important;text-transform:uppercase !important;letter-spacing:0.1em !important;margin-bottom:1rem !important;">Calorie Target</p>' +
              '<div class="dev_chefpress_plan_space-y-3">' +
                (function() {
                  var recs = getCalorieRecommendations();
                  return Object.values(recs).map(function(r, index) {
                    var isMaintain = index === 0;
                    return '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center" style="background:' + (isMaintain ? 'rgba(255,255,255,0.12)' : 'rgba(255,255,255,0.08)') + ' !important;border:1px solid rgba(255,255,255,0.12) !important;border-radius:1rem !important;overflow:hidden !important; display:flex !important; flex-direction:' + (isMobile ? 'column' : 'row') + '!important;">' +
                    '<div style="flex:1;padding:' + (isMobile ? '0.5rem' : '1rem') + ' !important;background:rgba(255,255,255,0.04) !important;width:' + (isMobile ? '100%' : 'auto') + '!important;">' +
                      '<p style="font-size:' + (isMobile ? '0.75rem' : '0.875rem') + ' !important;font-weight:900 !important;color:#fff !important;margin:0 !important;">' + r.label + '</p>' +
                      (r.weightLoss ? '<p style="font-size:0.75rem !important;color:rgba(255,255,255,0.75) !important;margin:0 !important;">' + r.weightLoss + '</p>' : '<div style="height:0.875rem !important;"></div>') +
                    '</div>' +
                    '<div style="min-width:' + (isMobile ? '100%' : '10rem') + ';padding:' + (isMobile ? '0.5rem' : '1rem') + ' !important;background:' + (isMaintain ? 'rgba(255,255,255,0.18)' : 'rgba(255,255,255,0.12)') + ' !important;display:flex;flex-direction:column;align-items:' + (isMobile ? 'flex-start' : 'flex-end') + ';justify-content:center !important;width:' + (isMobile ? '100%' : 'auto') + '!important;">' +
                      '<p style="font-size:' + (isMobile ? '1.25rem' : '1.5rem') + ' !important;font-weight:900 !important;color:#fff !important;margin:0 !important;">' + r.cals.toLocaleString() + '</p>' +
                      '<p style="font-size:0.75rem !important;font-weight:700 !important;color:rgba(255,255,255,0.8) !important;margin:0 !important;">' + r.percent + '%</p>' +
                      '<p style="font-size:0.75rem !important;color:rgba(255,255,255,0.7) !important;margin:0 !important;">Cal/day</p>' +
                    '</div>' +
                  '</div>';
                  }).join('');
                })() +
              '</div>' +
            '</div>' +
            '<div style="padding-top:' + (isMobile ? '1rem' : '1.5rem') + ' !important;border-top:1px solid rgba(255,255,255,0.1) !important;">' +
              '<p style="font-size:0.625rem !important;font-weight:700 !important;color:var(--emerald-300) !important;text-transform:uppercase !important;letter-spacing:0.1em !important;margin-bottom:0.25rem !important;">WEEKLY COST</p>' +
              '<p style="font-size:' + (isMobile ? '1.5rem' : '1.875rem') + ' !important;font-weight:900 !important;margin:0!important;">AED ' + pricing.final + '</p>' +
              '<p style="font-size:0.625rem !important;font-weight:700 !important;color:rgba(110,231,183,0.6) !important;margin-top:0.25rem !important;">' + state.selectedDays.length + ' days / week</p>' +
            '</div>' +
          '</div>' +
          '<div style="background:#fff !important;border:1px solid var(--gray-100) !important;padding:1rem !important;border-radius:1rem !important;display:flex !important;align-items:center !important;gap:0.75rem !important;">' +
            '<div style="width:2rem !important;height:2rem !important;background:var(--emerald-50) !important;border-radius:0.5rem !important;display:flex !important;align-items:center !important;justify-content:center !important;flex-shrink:0!important;">' +
              '<i data-lucide="truck" style="color:var(--emerald-600) !important;width:1rem !important;height:1rem !important;"></i>' +
            '</div>' +
            '<p style="font-size:0.625rem !important;font-weight:700 !important;color:var(--gray-500) !important;line-height:1.4 !important;margin:0!important;">Free delivery included</p>' +
          '</div>' +
        '</div>' +
      '</div>';

    if (window.innerWidth >= 640) el.querySelector('#dev_chefpress_plan_meal-cards').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
    if (window.innerWidth >= 768) {
      el.querySelector('#dev_chefpress_plan_box-grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
    }
    if (window.innerWidth >= 1024) {
      el.querySelector('#dev_chefpress_plan_box-grid').style.gridTemplateColumns = 'repeat(3,minmax(0,1fr))';
      el.querySelector('#dev_chefpress_plan_box-left').style.gridColumn = 'span 2';
    }
  }

  // Step 9 – Menu Selection
  var currentSlotId = '';

  function waitForWeeklyMenuReady(callback, timeoutMs) {
    timeoutMs = timeoutMs || 5000;
    var start = Date.now();

    var interval = setInterval(function () {
      var applyBtn = document.querySelector('#cp_weekly_filter_sidebar .cp_weekly_menu_sidebar_apply');
      var sidebar = document.querySelector('#cp_weekly_filter_sidebar');
      var filterBtn = document.querySelector('#cp_weekly_filter_sidebar .cp_weekly_menu_sidebar_btn');

      if (applyBtn && sidebar && filterBtn) {
        clearInterval(interval);
        callback();
        return;
      }

      if (Date.now() - start >= timeoutMs) {
        clearInterval(interval);
        callback();
      }
    }, 100);
  }

        // Add to slot button handler
  $(document).on('click', '.cp_weekly_menu_card_add_slot', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const recipeId = $(this).data('recipe-id');
      const recipeData = $(this).data('full-recipe-data');
      if (!recipeId) {
          return;
      }
      openWeeklyMenuSlotPopup(recipeId, recipeData);
  });

  function getActiveMealTypeFromDropdown() {
    var $active = $('#cp_weekly_mealtype_dropdown a.active');
    if (!$active.length) {
      return '';
    }
    var mealType = $active.data('meal-type');
    return mealType ? mealType.toString().trim().toLowerCase() : '';
  }

  function titleCaseMealType(mealType) {
    if (!mealType) {
      return '';
    }
    var normalized = mealType.toString().trim().toLowerCase();
    switch (normalized) {
      case 'breakfast': return 'Breakfast';
      case 'lunch': return 'Lunch';
      case 'dinner': return 'Dinner';
      case 'snacks': return 'Snacks';
      default: return normalized.charAt(0).toUpperCase() + normalized.slice(1);
    }
  }

  function getSlotOptionsForPopup() {
    var mealType = getActiveMealTypeFromDropdown();
    var selectedDays = Array.isArray(state.selectedDays) ? state.selectedDays : [];
    var mealTypes = [];

    if (mealType) {
      mealTypes.push(titleCaseMealType(mealType));
    } else {
      $.each(state.mealQuantities || {}, function (meal, qty) {
        if (qty > 0) {
          mealTypes.push(meal);
        }
      });
    }

    var slotOptions = [];
    selectedDays.forEach(function (day) {
      mealTypes.forEach(function (meal) {
        slotOptions.push({
          slotId: day + '-' + meal,
          day: day,
          meal: meal
        });
      });
    });

    return slotOptions;
  }

  function openWeeklyMenuSlotPopup(recipeId, recipeData) {
    var slotOptions = getSlotOptionsForPopup();
    var mealType = getActiveMealTypeFromDropdown();
    var labelMeal = mealType ? titleCaseMealType(mealType) : 'Meal';
    var titleText = mealType ? 'Assign to ' + labelMeal + ' slots' : 'Choose slots to assign';

    if (!slotOptions.length) {
      alert('No slots are available for the current selection. Please select days and meal types first.');
      return;
    }

    var html = '<div class="cp_weekly_slot_popup_header">' +
      '<h3 class="cp_weekly_slot_popup_title">' + titleText + '</h3>' +
      '<button type="button" class="cp_weekly_slot_popup_close_btn" aria-label="Close">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
      '</button>' +
    '</div>';
    html += '<div class="cp_weekly_slot_popup_body">';
    html += '<p class="cp_weekly_slot_popup_description">Select one or more slots to assign this recipe:</p>';
    html += '<div class="cp_weekly_slot_popup_list">';

    slotOptions.forEach(function (option, index) {
      var occupied = state.menu && state.menu[option.slotId];
      var label = option.day + ' — ' + option.meal;
      html += '<label class="cp_weekly_slot_popup_option' + (occupied ? ' cp_weekly_slot_popup_option_disabled' : '') + '">';
      html += '<input type="checkbox" name="cp_weekly_slot_option" value="' + option.slotId + '"' + (occupied ? ' disabled' : ' checked') + ' class="cp_weekly_slot_popup_checkbox">';
      html += '<span class="cp_weekly_slot_popup_label">' + label;
      if (occupied) {
        html += ' <span class="cp_weekly_slot_popup_occupied">(already assigned)</span>';
      }
      html += '</span></label>';
    });

    html += '</div></div>';
    html += '<div class="cp_weekly_slot_popup_actions">';
    html += '<button type="button" id="cp_weekly_slot_popup_cancel" class="dev_chefpress_plan_btn-outline">Cancel</button>';
    html += '<button type="button" id="cp_weekly_slot_popup_confirm" class="dev_chefpress_plan_btn-primary">OK</button>';
    html += '</div>';
    var $popup = $('#cp_weekly_slot_popup');
    if (!$popup.length) {
      $popup = $(
        '<div id="cp_weekly_slot_popup" data-recipe-data=\'' + JSON.stringify(recipeData) + '\' class="cp_weekly_slot_popup">' +
          '<div class="cp_weekly_slot_popup_overlay"></div>' +
          '<div class="cp_weekly_slot_popup_dialog">' +
            '<div id="cp_weekly_slot_popup_content"></div>' +
          '</div>' +
        '</div>'
      );
      $('body').append($popup);
    }

    $popup.find('#cp_weekly_slot_popup_content').html(html);
    $popup.data('recipe-id', recipeId);
    $popup.addClass('cp_weekly_slot_popup_active');
    $('body').css('overflow', 'hidden !important');
  }

  function closeWeeklyMenuSlotPopup() {
    var $popup = $('#cp_weekly_slot_popup');
    if ($popup.length) {
      $popup.removeClass('cp_weekly_slot_popup_active');
    }
    $('body').css('overflow', '');
  }

  function assignRecipeToSlotIds(recipeId, slotIds, recipeData) {
    console.log('Assigning recipe ID', recipeId, 'to slot IDs:', slotIds, 'with recipe data:', recipeData);
    if (!recipeId || !Array.isArray(slotIds) || !slotIds.length) {
      return;
    }
    slotIds.forEach(function (slotId) {
      if (slotId) {
        state.menu[slotId] = recipeId;

        // Find the corresponding slot in state.slots and add recipe data
        if (Array.isArray(state.slots)) {
          var slotIndex = state.slots.findIndex(function (slot) {
            return slot.id === slotId;
          });
          if (slotIndex !== -1) {
            state.slots[slotIndex].recipeSelected = recipeData;
            console.log('Updated slot at index', slotIndex, ':', state.slots[slotIndex]);
          }
        }
      }
    });
    console.log('Final state.slots:', state.slots);
    renderStep();

    // Update popup content if it's currently open
    if ($('#dev_chefpress_step9_popup:visible').length) {
      updatePopupContent();
    }
  }

  $(document).on('click', '#cp_weekly_slot_popup_cancel, .cp_weekly_slot_popup_close_btn, .cp_weekly_slot_popup_overlay', function (e) {
    if ($(e.target).is('#cp_weekly_slot_popup_confirm')) {
      return;
    }
    closeWeeklyMenuSlotPopup();
  });

  $(document).on('click', '#cp_weekly_slot_popup_confirm', function () {
    var $popup = $('#cp_weekly_slot_popup');
    var recipeId = $popup.data('recipe-id');
    var recipeData = $popup.data('recipe-data');
    var slotIds = [];
    console.log('Assigning recipe ID', recipeId, 'to selected slots. Recipe data:', recipeData);
    $popup.find('input[name="cp_weekly_slot_option"]:checked').each(function () {
      slotIds.push($(this).val());
    });

    if (!slotIds.length) {
      alert('Please select at least one slot before continuing.');
      return;
    }

    closeWeeklyMenuSlotPopup();
    assignRecipeToSlotIds(recipeId, slotIds, recipeData);

    // After a brief delay for rendering, click the first empty slot
    setTimeout(function () {
      var $firstEmptySlot = $('.dev_our_plans_slot_item').not('.dev_our_plans_slot_item_filled').first();
      if ($firstEmptySlot.length) {
        $firstEmptySlot.click();
      }
    }, 100);
  });

  function applyWeeklyMenuFilters() {
    if (state.selectedAllergens && state.selectedAllergens.length > 0) {
      state.selectedAllergens.forEach(function (allergen) {
        var normalizedAllergen = allergen.toLowerCase();
        var button = document.querySelector('#cp_weekly_filter_sidebar button[data-allergen="' + normalizedAllergen + '"]');
        if (button && !button.classList.contains('active')) {
          button.classList.add('active');
        }
      });
    }

    if (state.dietType) {
      var normalizedDiet = state.dietType.toLowerCase();
      var dietButton = document.querySelector('#cp_weekly_filter_sidebar button[data-recipe-tag="' + normalizedDiet + '"]');
      if (dietButton && !dietButton.classList.contains('active')) {
        dietButton.classList.add('active');
      }
    }

    var mealType = '';
    if (Array.isArray(state.slots)) {
      // Find the first empty slot (one without recipeSelected)
      var firstEmptySlot = state.slots.find(function(slot) {
        return !slot.recipeSelected;
      });
      if (firstEmptySlot) {
        mealType = firstEmptySlot.meal || '';
      }
    }

    if (!mealType && state.mealQuantities && typeof state.mealQuantities === 'object') {
      ['Breakfast', 'Lunch', 'Dinner', 'Snacks'].some(function(meal) {
        if (state.mealQuantities[meal] > 0) {
          mealType = meal;
          return true;
        }
        return false;
      });
    }

    if (mealType) {
      mealType = mealType.toString().toLowerCase();
      if (mealType === 'snacks') {
        mealType = 'snack';
      }
      if (typeof window.cpWeeklySetMealTypeFilter === 'function') {
        window.cpWeeklySetMealTypeFilter(mealType);
      }
    }

    var applyBtn = document.querySelector('#cp_weekly_filter_sidebar .cp_weekly_menu_sidebar_apply');
    if (applyBtn) {
      applyBtn.dispatchEvent(new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
        view: window
      }));
    }
  }

  function renderMenuSelection(el) {
    // Get the pre-rendered weekly menu container
    var weeklyMenuContainer = document.getElementById('dev_chefpress_weekly_menu_container');
    if (!weeklyMenuContainer) {
      el.innerHTML = '<p>Weekly menu component not found.</p>';
      return;
    }

    // Keep the weekly menu container in its original DOM location and control visibility by CSS
    weeklyMenuContainer.style.display = 'block';
    el.innerHTML = '';

    // Initialize Step 9 popup (button and modal)
    injectStep9PopupStyles();
    initStep9Popup();

    // Initialize weekly menu JS if available
    if (typeof initWeeklyMenu === 'function') {
      initWeeklyMenu();
    }

    // Auto-apply filters after the weekly menu is ready
    waitForWeeklyMenuReady(function () {
      applyWeeklyMenuFilters();
    });

    // Render lucide icons
    if (window.lucide) {
      window.lucide.createIcons();
    }
  }

  // Step 10 – Summary
  function renderSummary(el) {
    var pricing = calculatePricing();
    var totalCals = 0, totalProtein = 0, totalCarbs = 0;
    console.log('Calculating summary for menu:', state);

    if (Array.isArray(state.slots)) {
      state.slots.forEach(function(slot) {
        var recipe = slot.recipeSelected;
        if (!recipe && slot.id && state.menu && state.menu[slot.id]) {
          var rid = state.menu[slot.id];
          recipe = RECIPES.find(function(x) { return x.id === rid; });
        }

        if (recipe) {
          var cal = parseFloat(recipe.calories);
          var prot = parseFloat(recipe.protein);
          var carbs = parseFloat(recipe.carbs);

          if (!isNaN(cal)) totalCals += cal;
          if (!isNaN(prot)) totalProtein += prot;
          if (!isNaN(carbs)) totalCarbs += carbs;
        }
      });
    }

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4">Order Summary</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500">Review your nutritional snapshot and billing details.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-8 dev_chefpress_plan_mb-8" id="dev_chefpress_plan_summary-grid" style="grid-template-columns:repeat(1,minmax(0,1fr)) !important;">' +
        '<div class="dev_chefpress_plan_space-y-6">' +
          '<div style="background:var(--emerald-50) !important;padding:1.5rem !important;border-radius:2rem !important;">' +
            '<h3 style="font-size:0.875rem !important;font-weight:700 !important;color:var(--emerald-600) !important;text-transform:uppercase !important;letter-spacing:0.1em !important;margin-bottom:1rem !important;">Weekly Nutrition</h3>' +
            '<div class="dev_chefpress_plan_space-y-4">' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between dev_chefpress_plan_items-center">' +
                '<span style="color:var(--gray-600) !important;font-weight:500 !important;">Total Calories</span>' +
                '<span style="color:var(--emerald-900) !important;font-weight:900 !important;">' + totalCals + ' kcal</span>' +
              '</div>' +
              '<div style="width:100% !important;height:0.5rem !important;background:var(--emerald-100) !important;border-radius:9999px !important;overflow:hidden !important;">' +
                '<div style="height:100% !important;background:var(--emerald-500) !important;width:85% !important;"></div>' +
              '</div>' +
              '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-4 dev_chefpress_plan_pt-4" style="grid-template-columns:repeat(2,minmax(0,1fr)) !important;">' +
                '<div><p style="font-size:0.625rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;">Protein</p><p style="font-size:1.25rem !important;font-weight:900 !important;color:var(--emerald-900) !important;">' + totalProtein + 'g</p></div>' +
                '<div><p style="font-size:0.625rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;">Carbs</p><p style="font-size:1.25rem !important;font-weight:900 !important;color:var(--emerald-900) !important;">' + totalCarbs + 'g</p></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div style="background:#fff !important;border:1px solid var(--gray-100) !important;padding:1.5rem !important;border-radius:2rem !important;box-shadow:0 1px 3px rgba(0,0,0,0.05) !important;">' +
            '<h3 style="font-size:0.875rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;letter-spacing:0.1em !important;margin-bottom:1rem !important;">Plan Details</h3>' +
            '<div class="dev_chefpress_plan_space-y-2" style="font-size:0.875rem !important;">' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between"><span style="color:var(--gray-500) !important;">Goal</span><span style="font-weight:700 !important;color:var(--gray-900) !important;">' + state.goal + '</span></div>' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between"><span style="color:var(--gray-500) !important;">Diet</span><span style="font-weight:700 !important;color:var(--gray-900) !important;">' + state.dietType + '</span></div>' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between"><span style="color:var(--gray-500) !important;">Duration</span><span style="font-weight:700 !important;color:var(--gray-900) !important;">' + state.planDuration + '</span></div>' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between"><span style="color:var(--gray-500) !important;">Delivery</span><span style="font-weight:700 !important;color:var(--gray-900) !important;">' + state.selectedDays.length + ' Days / Week</span></div>' +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="dev_chefpress_plan_space-y-6">' +
          '<div style="background:#fff !important;border:1px solid var(--gray-100) !important;padding:1.5rem !important;border-radius:2rem !important;box-shadow:0 1px 3px rgba(0,0,0,0.05) !important;">' +
            '<h3 style="font-size:0.875rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;letter-spacing:0.1em !important;margin-bottom:1rem !important;">Billing Details</h3>' +
            '<div class="dev_chefpress_plan_space-y-2" style="font-size:0.875rem !important;">' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between"><span style="color:var(--gray-500) !important;">Base Price (' + state.selectedDays.length + ' days)</span><span style="font-weight:700 !important;color:var(--gray-900) !important;">AED ' + pricing.base + '</span></div>' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between" style="color:var(--emerald-600) !important;font-weight:700 !important;"><span>Total Discount (' + pricing.discount + '%)</span><span>-AED ' + (Number(pricing.base) * (Number(pricing.discount) / 100)).toFixed(2) + '</span></div>' +
              '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between dev_chefpress_plan_items-end dev_chefpress_plan_pt-4" style="border-top:1px solid var(--gray-50) !important;">' +
                '<span style="font-weight:700 !important;color:var(--gray-900) !important;">Total to Pay</span>' +
                '<span style="font-size:1.875rem !important;font-weight:900 !important;color:var(--emerald-900) !important;">AED ' + pricing.final + '</span>' +
              '</div>' +
            '</div>' +
            '<p style="font-size:0.625rem !important;color:var(--gray-400) !important;margin-top:1rem !important;line-height:1.625 !important;">Your subscription will renew automatically every ' + state.planDuration + '. You can cancel or modify anytime before your next billing cycle.</p>' +
          '</div>' +
        '</div>' +
      '</div>';

    if (window.innerWidth >= 768) el.querySelector('#dev_chefpress_plan_summary-grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
  }

  // Step 11 – Calendar
  function renderCalendar(el) {
    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4">When should we start?</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500">We need 48 hours to prepare your fresh ingredients.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_mb-8" style="max-width:28rem!important;margin:0 auto!important;">' +
        '<div style="background:#fff !important;border:1px solid var(--gray-100) !important;border-radius:2rem !important;padding:1.5rem !important;box-shadow:0 1px 3px rgba(0,0,0,0.05) !important;display:flex !important;justify-content:center !important;">' +
          '<div id="dev_chefpress_plan_start-date-picker" style="width:100% !important;"></div>' +
        '</div>' +
      '</div>';
  }

  // Step 12 – Delivery Slot
  function renderDeliverySlot(el) {
    var slots = [
      { id: 'night', label: 'Night Before', time: '6:00 PM - 10:00 PM', icon: 'moon' },
      { id: 'morning', label: 'Morning', time: '7:00 AM - 11:00 AM', icon: 'sun' }
    ];
    var instructions = ['Use cooler bag', 'Leave at door', 'Call on arrival', 'Ring doorbell'];

    var slotCards = slots.map(function(s) {
      var isA = state.deliverySlot === s.id;
      return '<div onclick="setSlot(\'' + s.id + '\')" class="dev_chefpress_plan_card-selectable ' + (isA ? 'dev_chefpress_plan_active' : '') + ' dev_chefpress_plan_flex dev_chefpress_plan_flex-col dev_chefpress_plan_items-center dev_chefpress_plan_text-center dev_chefpress_plan_gap-4" style="padding:1.5rem !important;">' +
        '<div style="width:3.5rem !important;height:3.5rem !important;background:var(--emerald-100) !important;border-radius:1rem !important;display:flex !important;align-items:center !important;justify-content:center !important;color:var(--emerald-600) !important;">' +
          '<i data-lucide="' + s.icon + '" style="width:1.75rem !important;height:1.75rem !important;"></i>' +
        '</div>' +
        '<div>' +
          '<h3 style="font-size:1.125rem !important;font-weight:700 !important;color:var(--gray-900) !important;">' + s.label + '</h3>' +
          '<p style="font-size:0.75rem !important;color:var(--gray-500) !important;">' + s.time + '</p>' +
        '</div>' +
      '</div>';
    }).join('');

    var instrBtns = instructions.map(function(inst) {
      var sel = state.deliveryInstructions.indexOf(inst) !== -1;
      return '<button onclick="toggleInstruction(\'' + inst + '\')" style="padding:0.75rem 1.5rem !important;border-radius:0.75rem !important;border:2px solid ' + (sel ? 'var(--emerald-500)' : 'var(--gray-100)') + ' !important;background:' + (sel ? 'var(--emerald-50)' : 'transparent') + ' !important;color:' + (sel ? 'var(--emerald-700)' : 'var(--gray-400)') + ' !important;font-weight:700 !important;font-size:0.875rem !important;cursor:pointer !important;transition:all 0.2s !important;font-family:inherit !important;">' + inst + '</button>';
    }).join('');

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4">Delivery Window</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500">Pick a time that works best for your schedule.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-6 dev_chefpress_plan_mb-8" id="dev_chefpress_plan_slot-grid" style="grid-template-columns:repeat(1,minmax(0,1fr)) !important;">' + slotCards + '</div>' +
      '<div class="dev_chefpress_plan_space-y-4 dev_chefpress_plan_mb-8">' +
        '<p style="font-size:0.875rem !important;font-weight:700 !important;color:var(--gray-700) !important;">Delivery Instructions</p>' +
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_flex-wrap dev_chefpress_plan_gap-3">' + instrBtns + '</div>' +
      '</div>';

    if (window.innerWidth >= 768) {
      var slotGrid = el.querySelector('#dev_chefpress_plan_slot-grid');
      if (slotGrid) {
        slotGrid.style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
      }
    }
  }

  // Step 13 – Address
  function renderAddress(el) {
    var typeBtns = ['Apartment', 'Home', 'Office'].map(function(type) {
      var sel = state.address.type === type;
      return '<button onclick="setAddressType(\'' + type + '\')" style="flex:1 !important;padding:0.75rem !important;border-radius:1rem !important;border:2px solid ' + (sel ? 'var(--emerald-500)' : 'var(--gray-100)') + ' !important;background:' + (sel ? 'var(--emerald-50)' : 'transparent') + ' !important;color:' + (sel ? 'var(--emerald-700)' : 'var(--gray-400)') + ' !important;font-size:0.875rem !important;font-weight:700 !important;cursor:pointer !important;transition:all 0.2s !important;font-family:inherit !important;">' + type + '</button>';
    }).join('');

    var fullscreenOverlay = state.isMapFullscreen ?
      '<div style="position:fixed !important;inset:0 !important;z-index:9999 !important;background:#fff !important;display:flex !important;flex-direction:column !important;">' +
        '<div style="padding:1rem !important;border-bottom:1px solid var(--emerald-100) !important;display:flex !important;align-items:center !important;justify-content:space-between !important;background:#fff !important;">' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_items-center dev_chefpress_plan_gap-3">' +
            '<div style="width:2.5rem !important;height:2.5rem !important;background:var(--emerald-100) !important;border-radius:0.75rem !important;display:flex !important;align-items:center !important;justify-content:center !important;color:var(--emerald-600) !important;">' +
              '<i data-lucide="map"></i>' +
            '</div>' +
            '<div><h3 style="font-weight:700 !important;color:var(--emerald-900) !important;">Select Delivery Location</h3><p style="font-size:0.625rem !important;color:var(--gray-500) !important;">Drag marker to your exact building</p></div>' +
          '</div>' +
          '<button onclick="toggleMapFullscreen()" style="width:2.5rem !important;height:2.5rem !important;border-radius:0.75rem !important;border:2px solid var(--gray-100) !important;display:flex !important;align-items:center !important;justify-content:center !important;color:var(--gray-400) !important;background:transparent !important;cursor:pointer !important;transition:all 0.2s !important;">' +
            '<i data-lucide="x"></i>' +
          '</button>' +
        '</div>' +
        '<div style="flex:1 !important;position:relative !important;">' +
          '<div id="dev_chefpress_plan_fullscreen-delivery-map" style="width:100% !important;height:100% !important;"></div>' +
          '<div style="position:absolute !important;top:1.5rem !important;left:50% !important;transform:translateX(-50%) !important;z-index:1000 !important;width:100% !important;max-width:32rem !important;padding:0 1.5rem !important;">' +
            '<div style="position:relative !important;">' +
              '<input type="text" id="dev_chefpress_plan_fullscreen-map-search" placeholder="Search for your building, street or area..." style="width:100% !important;padding:1rem 6rem 1rem 3rem !important;border-radius:1rem !important;border:2px solid var(--emerald-100) !important;outline:none !important;font-size:0.875rem !important;font-weight:500 !important;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25) !important;font-family:inherit !important;">' +
              '<i data-lucide="search" style="position:absolute !important;left:1rem !important;top:50% !important;transform:translateY(-50%) !important;width:1.25rem !important;height:1.25rem !important;color:var(--emerald-400) !important;"></i>' +
              '<button onclick="searchLocation(\'dev_chefpress_plan_fullscreen-map-search\')" style="position:absolute !important;right:0.5rem !important;top:50% !important;transform:translateY(-50%) !important;padding:0.5rem 1rem !important;background:var(--emerald-500) !important;color:#fff !important;font-size:0.75rem !important;font-weight:700 !important;border-radius:0.75rem !important;border:none !important;cursor:pointer !important;font-family:inherit !important;">Search</button>' +
            '</div>' +
          '</div>' +
          '<div style="position:absolute !important;bottom:2.5rem !important;left:50% !important;transform:translateX(-50%) !important;z-index:1000 !important;background:var(--emerald-900) !important;color:#fff !important;padding:0.75rem 1.5rem !important;border-radius:9999px !important;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25) !important;display:flex !important;align-items:center !important;gap:0.75rem !important;white-space:nowrap !important;">' +
            '<i data-lucide="info" style="width:1rem !important;height:1rem !important;color:var(--emerald-400) !important;"></i>' +
            '<span style="font-size:0.875rem !important;font-weight:500 !important;">Pinpoint your exact delivery entrance</span>' +
          '</div>' +
        '</div>' +
        '<div style="padding:1.5rem !important;background:#fff !important;border-top:1px solid var(--emerald-100) !important;">' +
          '<button onclick="toggleMapFullscreen()" class="dev_chefpress_plan_btn-primary dev_chefpress_plan_w-full" style="padding:1rem !important;border-radius:1rem !important;box-shadow:0 10px 15px -3px rgba(16,185,129,0.2) !important;">Confirm Location & Close</button>' +
        '</div>' +
      '</div>' : '';

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4">Where to deliver?</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500">Provide your exact location for seamless delivery.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_mb-8 dev_chefpress_plan_space-y-6" style="max-width:42rem !important;margin:0 auto !important;">' +
        '<div style="position:relative !important;">' +
          '<input type="text" id="dev_chefpress_plan_map-search" placeholder="Search for your building, street or area..." style="width:100% !important;padding:1rem 6rem 1rem 3rem !important;border-radius:1rem !important;border:2px solid var(--emerald-100) !important;outline:none !important;font-size:0.875rem !important;font-weight:500 !important;font-family:inherit !important;transition:all 0.2s !important;">' +
          '<i data-lucide="search" style="position:absolute !important;left:1rem !important;top:50% !important;transform:translateY(-50%) !important;width:1.25rem !important;height:1.25rem !important;color:var(--emerald-400) !important;"></i>' +
          '<button onclick="searchLocation(\'dev_chefpress_plan_map-search\')" style="position:absolute !important;right:0.5rem !important;top:50% !important;transform:translateY(-50%) !important;padding:0.5rem 1rem !important;background:var(--emerald-500) !important;color:#fff !important;font-size:0.75rem !important;font-weight:700 !important;border-radius:0.75rem !important;border:none !important;cursor:pointer !important;font-family:inherit !important;">Search</button>' +
        '</div>' +
        '<div style="position:relative !important;width:100% !important;height:16rem !important;border-radius:1.5rem !important;overflow:hidden !important;border:2px solid var(--emerald-100) !important;box-shadow:0 1px 3px rgba(0,0,0,0.05) !important;">' +
          '<div id="dev_chefpress_plan_delivery-map" style="width:100% !important;height:100% !important;z-index:10 !important;"></div>' +
          '<button onclick="toggleMapFullscreen()" style="position:absolute !important;top:1rem !important;right:1rem !important;z-index:20 !important;width:2.5rem !important;height:2.5rem !important;background:#fff !important;border-radius:0.75rem !important;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1) !important;border:1px solid var(--emerald-100) !important;display:flex !important;align-items:center !important;justify-content:center !important;color:var(--emerald-600) !important;cursor:pointer !important;transition:all 0.2s !important;">' +
            '<i data-lucide="maximize-2" style="width:1.25rem !important;height:1.25rem !important;"></i>' +
          '</button>' +
          '<div style="position:absolute !important;bottom:1rem !important;left:50% !important;transform:translateX(-50%) !important;z-index:20 !important;background:rgba(255,255,255,0.9) !important;backdrop-filter:blur(8px) !important;padding:0.5rem 1rem !important;border-radius:9999px !important;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1) !important;border:1px solid var(--emerald-100) !important;">' +
            '<p style="font-size:0.625rem !important;font-weight:700 !important;color:var(--emerald-900) !important;display:flex !important;align-items:center !important;gap:0.5rem !important;">' +
              '<i data-lucide="map-pin" style="width:0.75rem !important;height:0.75rem !important;color:var(--emerald-500) !important;"></i>' +
              'Drag marker or click map to set location' +
            '</p>' +
          '</div>' +
        '</div>' +
        '<div class="dev_chefpress_plan_flex dev_chefpress_plan_gap-4">' + typeBtns + '</div>' +
        '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-4" id="dev_chefpress_plan_addr-grid" style="grid-template-columns:repeat(1,minmax(0,1fr)) !important;">' +
          '<div id="dev_chefpress_plan_addr-building" style="grid-column:span 1 !important;">' +
            '<label style="display:block !important;font-size:0.75rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;margin-bottom:0.5rem !important;">Building Name / Villa Number</label>' +
            '<input type="text" id="dev_chefpress_plan_addr-building-input" value="' + state.address.building + '" class="dev_chefpress_plan_input-field" placeholder="e.g. Burj Khalifa">' +
          '</div>' +
          '<div>' +
            '<label style="display:block !important;font-size:0.75rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;margin-bottom:0.5rem !important;">Floor</label>' +
            '<input type="text" id="dev_chefpress_plan_addr-floor" value="' + state.address.floor + '" class="dev_chefpress_plan_input-field" placeholder="e.g. 12">' +
          '</div>' +
          '<div>' +
            '<label style="display:block !important;font-size:0.75rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;margin-bottom:0.5rem !important;">Flat / Office Number</label>' +
            '<input type="text" id="dev_chefpress_plan_addr-flat" value="' + state.address.flat + '" class="dev_chefpress_plan_input-field" placeholder="e.g. 1204">' +
          '</div>' +
          '<div id="dev_chefpress_plan_addr-details-wrap" style="grid-column:span 1 !important;">' +
            '<label style="display:block !important;font-size:0.75rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;margin-bottom:0.5rem !important;">Additional Details</label>' +
            '<textarea id="dev_chefpress_plan_addr-details" class="dev_chefpress_plan_input-field" style="height:6rem !important;resize:none !important;" placeholder="e.g. Near the main gate, code 1234...">' + state.address.details + '</textarea>' +
          '</div>' +
        '</div>' +
      '</div>' +
      fullscreenOverlay;

    if (window.innerWidth >= 640) {
      el.querySelector('#dev_chefpress_plan_addr-grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
      el.querySelector('#dev_chefpress_plan_addr-building').style.gridColumn = 'span 2';
      el.querySelector('#dev_chefpress_plan_addr-details-wrap').style.gridColumn = 'span 2';
    }

    $('#dev_chefpress_plan_map-search').on('keypress', function(e) { if (e.key === 'Enter') searchLocation('dev_chefpress_plan_map-search'); });
    $('#dev_chefpress_plan_fullscreen-map-search').on('keypress', function(e) { if (e.key === 'Enter') searchLocation('dev_chefpress_plan_fullscreen-map-search'); });
    $('#dev_chefpress_plan_addr-building-input').on('change', function() { state.address.building = $(this).val(); });
    $('#dev_chefpress_plan_addr-floor').on('change', function() { state.address.floor = $(this).val(); });
    $('#dev_chefpress_plan_addr-flat').on('change', function() { state.address.flat = $(this).val(); });
    $('#dev_chefpress_plan_addr-details').on('change', function() { state.address.details = $(this).val(); });
  }

  // Step 14 – Payment
  function renderPayment(el) {
    var pricing = calculatePricing();
    var label = state.planDuration === '1 Week' ? 'Weekly Total' : 'Monthly Total';
    var planDisc = PLAN_DISCOUNTS[state.planDuration || '1 Week'];

    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center dev_chefpress_plan_mb-8">' +
        '<h2 class="dev_chefpress_plan_text-4xl dev_chefpress_plan_font-bold dev_chefpress_plan_text-emerald-900 dev_chefpress_plan_mb-4">Secure Checkout</h2>' +
        '<p class="dev_chefpress_plan_text-gray-500">Complete your subscription to start your journey.</p>' +
      '</div>' +
      '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-8 dev_chefpress_plan_mb-8" id="dev_chefpress_plan_pay-grid" style="grid-template-columns:repeat(1,minmax(0,1fr)) !important;">' +
        '<div class="dev_chefpress_plan_space-y-6">' +
          '<div style="background:var(--gray-50) !important;padding:1.5rem !important;border-radius:2rem !important;" class="dev_chefpress_plan_space-y-4">' +
            '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between" style="font-size:0.875rem !important;"><span style="color:var(--gray-500) !important;">Plan: ' + state.planDuration + '</span><span style="font-weight:700 !important;color:var(--gray-900) !important;">AED ' + pricing.base + '</span></div>' +
            '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between" style="font-size:0.875rem !important;color:var(--emerald-600) !important;font-weight:700 !important;"><span>Plan Discount</span><span>-' + planDisc + '%</span></div>' +
            (state.isPromoApplied ? '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between" style="font-size:0.875rem !important;color:var(--emerald-600) !important;font-weight:700 !important;"><span>Promo: ' + state.promoCode + '</span><span>-' + state.promoDiscount + '%</span></div>' : '') +
            '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between dev_chefpress_plan_items-end dev_chefpress_plan_pt-4" style="border-top:1px solid var(--gray-200) !important;">' +
              '<span style="font-weight:700 !important;color:var(--gray-900) !important;">' + label + '</span>' +
              '<span style="font-size:1.875rem !important;font-weight:900 !important;color:var(--emerald-900) !important;">AED ' + pricing.final + '</span>' +
            '</div>' +
          '</div>' +
          '<div style="display:flex !important;align-items:center !important;gap:1rem !important;padding:1rem !important;background:var(--emerald-50) !important;border-radius:1rem !important;border:1px solid var(--emerald-100) !important;">' +
            '<i data-lucide="shield-check" style="color:var(--emerald-500) !important;flex-shrink:0 !important;"></i>' +
            '<p style="font-size:0.625rem !important;color:var(--emerald-700) !important;font-weight:500 !important;">SSL Encrypted &amp; Secure Payment Processing</p>' +
          '</div>' +
        '</div>' +
        '<div class="dev_chefpress_plan_space-y-4">' +
          '<div class="dev_chefpress_plan_space-y-3">' +
            '<label style="display:block !important;font-size:0.625rem !important;font-weight:700 !important;color:var(--gray-400) !important;text-transform:uppercase !important;">Card Details</label>' +
            '<input type="text" class="dev_chefpress_plan_input-field" style="padding:0.625rem 1rem !important;" placeholder="Card Number">' +
            '<div class="dev_chefpress_plan_flex dev_chefpress_plan_gap-4">' +
              '<input type="text" class="dev_chefpress_plan_input-field" style="padding:0.625rem 1rem !important;" placeholder="MM/YY">' +
              '<input type="text" class="dev_chefpress_plan_input-field" style="padding:0.625rem 1rem !important;" placeholder="CVC">' +
            '</div>' +
            '<input type="text" class="dev_chefpress_plan_input-field" style="padding:0.625rem 1rem !important;" placeholder="Cardholder Name">' +
          '</div>' +
          '<button onclick="nextStep()" class="dev_chefpress_plan_btn-primary dev_chefpress_plan_w-full" style="padding:0.875rem !important;font-size:1.125rem !important;">Pay &amp; Subscribe</button>' +
          '<div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-center dev_chefpress_plan_gap-4">' +
            '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/Tabby_logo.svg/2560px-Tabby_logo.svg.png" style="height:0.75rem !important;opacity:0.4 !important;" alt="Tabby">' +
            '<img src="https://tamara.co/assets/images/tamara-logo.svg" style="height:0.75rem !important;opacity:0.4 !important;" alt="Tamara">' +
          '</div>' +
        '</div>' +
      '</div>';

    if (window.innerWidth >= 1024) el.querySelector('#dev_chefpress_plan_pay-grid').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr))';
  }

  // Step 15 – Success
  function renderSuccess(el) {
    el.innerHTML =
      '<div class="dev_chefpress_plan_text-center" style="padding:3rem 0 !important; !important">' +
        '<div class="dev_chefpress_plan_animate-bounce" style="width:6rem !important;height:6rem !important;background:var(--emerald-500) !important;border-radius:9999px !important;display:flex !important;align-items:center !important;justify-content:center !important;margin:0 auto 2rem !important;box-shadow:0 25px 50px -12px rgba(16,185,129,0.4) !important;">' +
          '<i data-lucide="check" style="color:#fff !important;width:3rem !important;height:3rem !important;"></i>' +
        '</div>' +
        '<h2 style="font-size:3rem !important;font-weight:900 !important;color:var(--emerald-900) !important;margin-bottom:1rem !important;font-family:\'Outfit\',sans-serif !important;">You\'re all set!</h2>' +
        '<p style="color:var(--gray-500) !important;font-size:1.125rem !important;max-width:28rem !important;margin:0 auto 3rem !important;">Your first box will arrive on <span style="font-weight:700 !important;color:var(--emerald-600) !important;">' + state.startDate + '</span>. Get ready for a healthier you!</p>' +
        '<div class="dev_chefpress_plan_grid dev_chefpress_plan_gap-4" style="max-width:32rem !important;margin:0 auto !important;grid-template-columns:repeat(1,minmax(0,1fr)) !important;" id="dev_chefpress_plan_success-btns">' +
          '<button onclick="location.reload()" class="dev_chefpress_plan_btn-primary">Go to Dashboard</button>' +
          '<button onclick="modifyPlan()" class="dev_chefpress_plan_btn-outline">Modify Subscription</button>' +
        '</div>' +
      '</div>';

    if (window.innerWidth >= 640) el.querySelector('#dev_chefpress_plan_success-btns').style.gridTemplateColumns = 'repeat(2,minmax(0,1fr)) !important';
  }

  // ─────────────────────────────────────────────────────────
  //  NAV BAR MANAGEMENT
  // ─────────────────────────────────────────────────────────
  function generateSlotsFromState() {
    var slots = [];
    if (!Array.isArray(state.selectedDays)) {
      state.slots = slots;
      state.currentSlotIndex = 0;
      return;
    }

    // Preserve recipe data from existing slots
    var oldSlotsMap = {};
    if (Array.isArray(state.slots)) {
      state.slots.forEach(function(slot) {
        oldSlotsMap[slot.id] = slot;
      });
    }

    state.selectedDays.forEach(function(day) {
      $.each(state.mealQuantities, function(meal, quantity) {
        quantity = Number(quantity) || 0;
        for (var i = 0; i < quantity; i++) {
          var id = day + '-' + meal + (quantity > 1 ? '-' + (i + 1) : '');
          var newSlot = { id: id, day: day, meal: meal };
          
          // Restore recipe data if it exists from previous slots
          if (oldSlotsMap[id] && oldSlotsMap[id].recipeSelected) {
            newSlot.recipeSelected = oldSlotsMap[id].recipeSelected;
          }
          
          slots.push(newSlot);
        }
      });
    });

    state.slots = slots;
    if (state.currentSlotIndex < 0) state.currentSlotIndex = 0;
    if (state.currentSlotIndex >= slots.length) state.currentSlotIndex = Math.max(0, slots.length - 1);
  }

  function updateArrowStates() {
    var inner = $('.dev_our_plans_slots_inner');
    if (!inner.length) return;
    var leftArrow = $('#dev_our_plans_slots_left');
    var rightArrow = $('#dev_our_plans_slots_right');
    var scrollLeft = inner.scrollLeft();
    var scrollWidth = inner[0].scrollWidth;
    var clientWidth = inner[0].clientWidth;
    leftArrow.prop('disabled', scrollLeft <= 0);
    rightArrow.prop('disabled', scrollLeft >= scrollWidth - clientWidth - 1);
  }

  function renderSlotsInNav() {
    var container = $('#dev_our_plans_slots_container');
    if (!container.length) return;

    container.empty();
    var isMobile = window.innerWidth <= 768;
    
    var inner = $('<div class="dev_our_plans_slots_inner"></div>');
    
    // Only create arrow buttons on desktop
    var leftArrow, rightArrow;
    if (!isMobile) {
      leftArrow = $('<button id="dev_our_plans_slots_left" class="dev_our_plans_slots_arrow dev_our_plans_slots_arrow_left" aria-label="Scroll slots left"><i data-lucide="chevron-left"></i></button>');
      rightArrow = $('<button id="dev_our_plans_slots_right" class="dev_our_plans_slots_arrow dev_our_plans_slots_arrow_right" aria-label="Scroll slots right"><i data-lucide="chevron-right"></i></button>');
      container.append(leftArrow);
    }
    
    container.append(inner);
    
    if (!isMobile) {
      container.append(rightArrow);

      // Attach click handlers
      leftArrow.on('click', function() {
        var slotWidth = $('.dev_our_plans_slot_item').outerWidth(true) || 200;
        inner.animate({ scrollLeft: inner.scrollLeft() - slotWidth }, 300, function() {
          updateArrowStates();
        });
      });
      rightArrow.on('click', function() {
        var slotWidth = $('.dev_our_plans_slot_item').outerWidth(true) || 200;
        inner.animate({ scrollLeft: inner.scrollLeft() + slotWidth }, 300, function() {
          updateArrowStates();
        });
      });
    }

    var slots = state.slots || [];
    if (!slots.length) {
      inner.append('<div class="dev_our_plans_slots_empty">No slots selected</div>');
      return;
    }

    slots.forEach(function(slot, index) {
      var label = slot.day + ' ' + slot.meal;
      var slotId = slot.day + '-' + slot.meal;
      var isFilled = state.menu && state.menu[slotId];
      var slotBtn = $('<button type="button" data-meal="' + slot.meal + '" class="dev_our_plans_slot_item' + (isFilled ? ' dev_our_plans_slot_item_filled' : '') + '" data-slot-index="' + index + '" data-slot-id="' + slotId + '" aria-label="' + label + '">' + label + '</button>');
      if (index === state.currentSlotIndex) {
        slotBtn.addClass('active');
      }
      slotBtn.on('click', function() {
        state.currentSlotIndex = index;
        renderSlotsInNav();
        // Update meal type dropdown to match the slot's meal
        var meal = $(this).data('meal').toLowerCase();
        var $mealOption = $('#cp_weekly_mealtype_dropdown a[data-meal-type="' + meal + '"]');
        if ($mealOption.length) {
          $mealOption.click();
        }
      });
      inner.append(slotBtn);
    });

    updateArrowStates();

    // Render lucide icons for the arrow buttons
    if (window.lucide) {
      window.lucide.createIcons();
    }
  }

  function updateNavBar() {
    var navBar = $('#dev_chefpress_plan_nav_bar');
    var backBtn = $('#dev_chefpress_plan_back_btn');
    var nextBtn = $('#dev_chefpress_plan_next_btn');
    var slotsContainer = $('#dev_our_plans_slots_container');

    generateSlotsFromState();

    // Show nav bar only when not in success (step 15), but show in step 9 for slots
    if (state.currentStep === 15) {
      navBar.hide();
      navBar.removeClass('dev_our_plans_slots_step');
    } else {
      navBar.show();
      backBtn.prop('disabled', state.currentStep === 1);
      nextBtn.prop('disabled', false);
    }

    // Show slots only in step 9
    if (state.currentStep === 9) {
      slotsContainer.show();
      navBar.addClass('dev_our_plans_slots_step');
      renderSlotsInNav();
    } else {
      slotsContainer.hide();
      navBar.removeClass('dev_our_plans_slots_step');
    }
  }

  function handleBack() {
    if (state.currentStep === 5 && state.hasAllergies === true) {
      handleAllergyBack();
    } else {
      prevStep();
    }
  }

  // ─────────────────────────────────────────────────────────
  //  GLOBAL EVENT HANDLERS
  // ─────────────────────────────────────────────────────────
  function setGoal(goal)         { state.goal = goal; renderStep(); }
  function updateWeight(val)     { state.weight = Math.max(30, state.weight + val); var i = document.getElementById('dev_chefpress_plan_weight-input'); if(i) i.value = state.weight; }
  function updateHeight(val)     { state.height = Math.max(100, state.height + val); var i = document.getElementById('dev_chefpress_plan_height-input'); if(i) i.value = state.height; }
  function updateAge(val)        { state.age = Math.max(10, Math.min(120, state.age + val)); var i = document.getElementById('dev_chefpress_plan_age-input'); if(i) i.value = state.age; }
  function setActivity(level)    { state.activityLevel = level; renderStep(); }

  function setHasAllergies(val) {
    state.hasAllergies = val;
    if (!val) { state.selectedAllergens = []; nextStep(); }
    else { renderStep(); }
  }
  function handleAllergyBack() {
    if (state.hasAllergies === null) { prevStep(); }
    else { state.hasAllergies = null; renderStep(); }
  }
  function toggleAllergen(allergen) {
    var idx = state.selectedAllergens.indexOf(allergen);
    if (idx !== -1) state.selectedAllergens.splice(idx, 1);
    else state.selectedAllergens.push(allergen);
    renderStep();
  }
  function setDiet(diet)         { state.dietType = diet; renderStep(); }
  function setPlan(plan)         { state.planDuration = plan; renderStep(); }

  function applyPromo() {
    var input = document.getElementById('dev_chefpress_plan_promo-input');
    if (!input) {
      return;
    }
    var code = input.value.toUpperCase().trim();
    if (code && Object.prototype.hasOwnProperty.call(PROMO_CODES, code)) {
      state.promoCode = code;
      state.promoDiscount = PROMO_CODES[code];
      state.isPromoApplied = true;
      renderStep();
      return;
    }
    alert('Invalid Promo Code');
  }
  function removePromo() { state.promoCode = ''; state.promoDiscount = 0; state.isPromoApplied = false; renderStep(); }

  function updateMealQuantity(meal, delta) {
    var cur = state.mealQuantities[meal] || 0;
    var next = Math.max(0, cur + delta);
    state.mealQuantities[meal] = next;
    if (next === 0) {
      $.each(Object.keys(state.menu), function(_, sid) {
        if (sid.indexOf(meal) !== -1) delete state.menu[sid];
      });
    }
    renderStep();
  }

  function toggleDay(day) {
    var idx = state.selectedDays.indexOf(day);
    if (idx !== -1) {
      state.selectedDays.splice(idx, 1);
      $.each(Object.keys(state.menu), function(_, sid) {
        if (sid.indexOf(day) === 0) delete state.menu[sid];
      });
    } else {
      state.selectedDays.push(day);
      var order = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      state.selectedDays.sort(function(a,b){ return order.indexOf(a) - order.indexOf(b); });
    }
    renderStep();
  }

  function filterMenu(cat) { state.menuFilter = cat; renderStep(); }

  function openRecipePicker(slotId) { currentSlotId = slotId; }

  function assignRecipe(recipeId) {
    if (!currentSlotId) {
      var days = state.selectedDays;
      var activeMeals = [];
      $.each(state.mealQuantities, function(m, q){ if (q > 0) activeMeals.push(m); });
      outer: for (var di = 0; di < days.length; di++) {
        for (var mi = 0; mi < activeMeals.length; mi++) {
          var sid = days[di] + '-' + activeMeals[mi];
          if (!state.menu[sid]) { currentSlotId = sid; break outer; }
        }
      }
    }
    if (currentSlotId) { state.menu[currentSlotId] = recipeId; currentSlotId = ''; renderStep(); }
  }

  function setSlot(slot)         { state.deliverySlot = slot; renderStep(); }
  function toggleInstruction(inst) {
    var idx = state.deliveryInstructions.indexOf(inst);
    if (idx !== -1) state.deliveryInstructions.splice(idx, 1);
    else state.deliveryInstructions.push(inst);
    renderStep();
  }
  function setAddressType(type)  { state.address.type = type; renderStep(); }
  function toggleMapFullscreen() { state.isMapFullscreen = !state.isMapFullscreen; renderStep(); }

  async function searchLocation(inputId) {
    var input = document.getElementById(inputId);
    var query = input ? input.value : '';
    if (!query) return;
    var btn = $(input).siblings('button')[0];
    if (btn) { btn.disabled = true; btn.innerText = 'Searching...'; }
    try {
      var response = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query));
      var data = await response.json();
      if (data && data.length > 0) {
        state.address.lat = parseFloat(data[0].lat);
        state.address.lng = parseFloat(data[0].lon);
        renderStep();
      } else { alert('Location not found. Please try a more specific address.'); }
    } catch(e) { alert('Search failed. Please try again.'); }
    finally { if (btn) { btn.disabled = false; btn.innerText = 'Search'; } }
  }

  function modifyPlan() {
    var originalPrice = Number(calculatePricing().final);
    var newPrice = originalPrice + 50;
    var diff = newPrice - originalPrice;
    alert('Modify Plan Logic:\nOriginal: AED ' + originalPrice + '\nNew: AED ' + newPrice + '\n' + (diff > 0 ? 'Additional Charge: AED ' + diff : 'Refund Amount: AED ' + Math.abs(diff)));
  }

  // ─────────────────────────────────────────────────────────
  //  STEP 9 – POPUP BUTTON
  // ─────────────────────────────────────────────────────────

  function initStep9Popup() {
    // Create and inject popup modal
    var popupHTML = `
      <div id="dev_chefpress_step9_popup" class="dev_chefpress_step9_popup_overlay">
        <div class="dev_chefpress_step9_popup_modal">
          <div class="dev_chefpress_step9_popup_header">
            <h3 class="dev_chefpress_step9_popup_title">Your Meal Plan</h3>
            <button class="dev_chefpress_step9_popup_close" aria-label="Close popup">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
          <div class="dev_chefpress_step9_popup_content">
            ${generateAssignedRecipesContent()}
          </div>
        </div>
      </div>
    `;

    // Remove existing popup if any
    $('#dev_chefpress_step9_popup').remove();

    // Append popup to body
    $('body').append(popupHTML);

    // Remove old event handlers to prevent duplicates
    $(document).off('click', '.dev_chefpress_step9_popup_close');
    $(document).off('click', '#dev_chefpress_step9_popup');
    $(document).off('keydown.step9popup');
    $(document).off('click', '.dev_chefpress_remove_recipe_btn');

    // Close button handler
    $(document).on('click', '.dev_chefpress_step9_popup_close', function(e) {
      e.preventDefault();
      e.stopPropagation();
      closeStep9Popup();
    });

    // Overlay click handler - only close if clicking on overlay itself
    $(document).on('click', '#dev_chefpress_step9_popup', function(e) {
      if (e.target === this) {
        closeStep9Popup();
      }
    });

    // Escape key handler
    $(document).on('keydown.step9popup', function(e) {
      if (e.key === 'Escape' && $('#dev_chefpress_step9_popup:visible').length) {
        closeStep9Popup();
      }
    });

    // Remove recipe button handler
    $(document).on('click', '.dev_chefpress_remove_recipe_btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var slotId = $(this).data('slot-id');
      var recipeId = $(this).data('recipe-id');
      removeRecipeFromSlot(slotId, recipeId);
    });

    // Add button to weekly menu header
    addStep9PopupButton();
  }

  function addStep9PopupButton() {
    // Only add if button doesn't exist and we're in step 9
    if ($('#dev_chefpress_step9_popup_btn').length) return;

    // Find the recipe grid and insert button before it
    var recipeGrid = $('#cp_weekly_recipe_grid');
    if (!recipeGrid.length) return;

    var buttonHTML = `
      <div class="dev_chefpress_step9_popup_button_container" style="padding: 1.5rem 1rem; text-align: center; border-bottom: 1px solid #f0f0f0;">
        <button id="dev_chefpress_step9_popup_btn" class="dev_chefpress_step9_popup_button" type="button" title="View your selected recipes" aria-label="View meal plan">
          <span>📋</span> View Meal Plan
        </button>
      </div>
    `;
    
    // Insert button before the recipe grid
    recipeGrid.before(buttonHTML);

    // Remove old handler first
    $(document).off('click', '#dev_chefpress_step9_popup_btn');

    // Add click handler - use event delegation
    $(document).on('click', '#dev_chefpress_step9_popup_btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      openStep9Popup();
    });
  }

  function openStep9Popup() {
    var popup = $('#dev_chefpress_step9_popup');
    if (popup.length) {
      updatePopupContent();
      popup.css('display', 'flex').fadeIn(300);
      // Add scroll lock to body
      $('body').css('overflow', 'hidden');
    }
  }

  function closeStep9Popup() {
    var popup = $('#dev_chefpress_step9_popup');
    if (popup.length) {
      popup.fadeOut(300, function() {
        $(this).css('display', 'none');
      });
      // Remove scroll lock
      $('body').css('overflow', '');
    }
  }

  function generateAssignedRecipesContent() {
    var assignedRecipes = [];

    // Collect assigned recipes from state.slots
    if (Array.isArray(state.slots)) {
      state.slots.forEach(function(slot) {
        if (slot.recipeSelected && slot.recipeSelected.title) {
          assignedRecipes.push({
            slotId: slot.id,
            slotName: slot.name || (slot.day && slot.meal ? slot.day + ' • ' + titleCaseMealType(slot.meal) : slot.id),
            recipeName: slot.recipeSelected.title,
            recipeId: slot.recipeSelected.id
          });
        }
      });
    }

    if (assignedRecipes.length === 0) {
      return `
        <div class="dev_chefpress_assigned_recipes_empty">
          <p class="dev_chefpress_assigned_recipes_empty_text">
            No recipes assigned yet. Start adding recipes to your meal slots!
          </p>
        </div>
      `;
    }

    var recipesHTML = assignedRecipes.map(function(assignment) {
      return `
        <div class="dev_chefpress_assigned_recipe_item">
          <div class="dev_chefpress_assigned_recipe_info">
            <div class="dev_chefpress_assigned_recipe_title">${assignment.recipeName}</div>
            <div class="dev_chefpress_assigned_recipe_slot">${assignment.slotName}</div>
          </div>
          <button class="dev_chefpress_remove_recipe_btn"
                  data-slot-id="${assignment.slotId}"
                  data-recipe-id="${assignment.recipeId}">
            Clear slot
          </button>
        </div>
      `;
    }).join('');

    return `
      <div class="dev_chefpress_assigned_recipes_section">
        <div class="dev_chefpress_assigned_recipes_header">
          <h4>Your Selected Recipes</h4>
          <p>Remove any assigned meal to empty the slot and choose a new recipe.</p>
        </div>
        <div class="dev_chefpress_assigned_recipes_list">
          ${recipesHTML}
        </div>
      </div>
    `;
  }

  function removeRecipeFromSlot(slotId, recipeId) {
    console.log('Removing recipe', recipeId, 'from slot', slotId);

    // Remove from state.menu
    if (state.menu && Object.prototype.hasOwnProperty.call(state.menu, slotId)) {
      delete state.menu[slotId];
    }

    // Remove from state.slots
    if (Array.isArray(state.slots)) {
      var slotIndex = state.slots.findIndex(function(slot) {
        return slot.id === slotId;
      });
      if (slotIndex !== -1) {
        state.slots[slotIndex].recipeSelected = null;
        console.log('Removed recipe from slot at index', slotIndex);
      }
    }

    // Refresh only the Step 9 slot nav and popup when on step 9.
    if (state.currentStep === 9) {
      renderSlotsInNav();
      updatePopupContent();
      return;
    }

    // Otherwise rerender the current step.
    renderStep();
    updatePopupContent();
  }

  function updatePopupContent() {
    var contentElement = $('.dev_chefpress_step9_popup_content');
    if (contentElement.length) {
      var newContent = generateAssignedRecipesContent();
      contentElement.html(newContent);
    }
  }

  // Add styles for the popup
  function injectStep9PopupStyles() {
    if ($('#dev_chefpress_step9_popup_styles').length) return;

    var styles = `
      <style id="dev_chefpress_step9_popup_styles">
        .dev_chefpress_step9_popup_overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: rgba(0, 0, 0, 0.5);
          display: none;
          align-items: center;
          justify-content: center;
          z-index: 99999;
        }

        .dev_chefpress_step9_popup_modal {
          background: #fff;
          border-radius: 1.25rem;
          box-shadow: 0 30px 45px rgba(15, 23, 42, 0.18);
          max-width: 960px;
          width: 88%;
          max-height: 88vh;
          overflow: hidden;
          animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
          from {
            opacity: 0;
            transform: translateY(20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        .dev_chefpress_step9_popup_header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 1.5rem 1.75rem;
          border-bottom: 1px solid #e5e7eb;
        }

        .dev_chefpress_step9_popup_title {
          font-size: 1.375rem;
        }

        .dev_chefpress_assigned_recipes_header {
          display: grid;
          gap: 0.35rem;
          margin-bottom: 1rem;
        }

        .dev_chefpress_assigned_recipes_header h4 {
          margin: 0;
          font-size: 1.125rem;
          font-weight: 700;
          color: #111827;
        }

        .dev_chefpress_assigned_recipes_header p {
          margin: 0;
          color: #4b5563;
          font-size: 0.95rem;
        }

        .dev_chefpress_assigned_recipes_section {
          padding: 1.5rem 1.75rem 2rem;
        }

        .dev_chefpress_assigned_recipes_empty {
          padding: 2rem 1rem;
          border-radius: 1rem;
          background: #f8fafc;
          border: 1px solid #e5e7eb;
          text-align: center;
        }

        .dev_chefpress_assigned_recipes_empty_text {
          margin: 0;
          color: #6b7280;
          font-style: italic;
          line-height: 1.75;
        }

        .dev_chefpress_assigned_recipes_list {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
          gap: 1rem;
        }

        .dev_chefpress_assigned_recipe_item {
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          padding: 1rem;
          border: 1px solid #e5e7eb;
          border-radius: 1rem;
          background: #f9fafb;
          min-height: 130px;
          gap: 1rem;
        }

        .dev_chefpress_assigned_recipe_info {
          display: grid;
          gap: 0.35rem;
        }

        .dev_chefpress_assigned_recipe_title {
          font-weight: 700;
          color: #111827;
          line-height: 1.3;
        }

        .dev_chefpress_assigned_recipe_slot {
          font-size: 0.9rem;
          color: #4b5563;
        }

        .dev_chefpress_remove_recipe_btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-width: 130px;
          border: none !important;
          outline: none !important;
          cursor: pointer;
          background: #ef4444 !important;
          color: #fff !important;
          padding: 0.85rem 1rem;
          border-radius: 0.75rem;
          font-weight: 700;
          font-size: 0.95rem;
          letter-spacing: 0.01em;
          transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .dev_chefpress_remove_recipe_btn {
          padding: 0 !important;
          background: none !important;
          color: black !important;
        }

        .dev_chefpress_remove_recipe_btn:hover {
          background: #dc2626 !important;
          transform: translateY(-1px);
          box-shadow: 0 12px 22px rgba(220, 38, 38, 0.18);
        }

        .dev_chefpress_step9_popup_content {
          padding: 1.5rem 1.75rem 2rem;
          line-height: 1.6;
          color: #374151;
          max-height: 74vh;
          overflow-y: auto;
        }

        .dev_chefpress_step9_popup_title {
          margin: 0;
          font-size: 1.25rem;
          font-weight: 700;
          color: #1f2937;
        }

        .dev_chefpress_step9_popup_close {
          background: none;
          border: none;
          cursor: pointer;
          color: #6b7280 !important;
          width: 36px;
          height: 36px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 0.5rem;
          padding: 0px !important;
          color: black !important;
          transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .dev_chefpress_step9_popup_close:hover {
          background-color: #f3f4f6;
          color: #111827;
          transform: translateY(-1px);
        }

        .dev_chefpress_step9_popup_close svg {
          width: 1rem;
          height: 1rem;
        }

        .dev_chefpress_step9_popup_content {
          padding: 1.5rem;
          line-height: 1.6;
          color: #374151;
          max-height: 70vh;
          overflow-y: auto;
        }

        .dev_chefpress_step9_popup_content p {
          margin: 0.75rem 0;
        }

        .dev_chefpress_step9_popup_content li {
          margin: 0.5rem 0;
          color: #4b5563;
        }

        .dev_chefpress_step9_popup_button_container {
          padding: 1rem;
          text-align: center;
        }

        .dev_chefpress_step9_popup_button {
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          padding: 0.625rem 1rem;
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
          color: #fff;
          border: none;
          border-radius: 0.5rem;
          font-size: 0.875rem;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s ease;
          box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .dev_chefpress_step9_popup_button:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .dev_chefpress_step9_popup_button:active {
          transform: translateY(0);
        }

        .dev_chefpress_step9_popup_button span {
          font-size: 1rem;
        }

        @media (max-width: 640px) {
          .dev_chefpress_step9_popup_modal {
            width: 95%;
          }

          .dev_chefpress_step9_popup_header {
            padding: 1rem;
          }

          .dev_chefpress_step9_popup_content {
            padding: 1rem;
          }

          .dev_chefpress_step9_popup_title {
            font-size: 1.1rem;
          }
        }
      </style>
    `;

    $('head').append(styles);
  }

  // ─────────────────────────────────────────────────────────
  //  INIT
  // ─────────────────────────────────────────────────────────
  
  // Expose all functions to global scope for HTML onclick handlers
  window.setGoal = setGoal;
  window.updateWeight = updateWeight;
  window.updateHeight = updateHeight;
  window.setActivity = setActivity;
  window.setHasAllergies = setHasAllergies;
  window.handleAllergyBack = handleAllergyBack;
  window.toggleAllergen = toggleAllergen;
  window.setDiet = setDiet;
  window.setPlan = setPlan;
  window.applyPromo = applyPromo;
  window.removePromo = removePromo;
  window.updateMealQuantity = updateMealQuantity;
  window.toggleDay = toggleDay;
  window.filterMenu = filterMenu;
  window.openRecipePicker = openRecipePicker;
  window.assignRecipe = assignRecipe;
  window.setSlot = setSlot;
  window.toggleInstruction = toggleInstruction;
  window.setAddressType = setAddressType;
  window.toggleMapFullscreen = toggleMapFullscreen;
  window.searchLocation = searchLocation;
  window.modifyPlan = modifyPlan;
  window.nextStep = nextStep;
  window.prevStep = prevStep;
  window.handleBack = handleBack;
  window.openStep9Popup = openStep9Popup;
  window.closeStep9Popup = closeStep9Popup;

  $(document).ready(function() {
    if (window.lucide) window.lucide.createIcons();
    renderStep();
  });
})(jQuery);

