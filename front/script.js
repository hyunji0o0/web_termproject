/*API 경로 설정 및 토큰 가져오기*/
// 본인의 로컬 경로에 맞게 수정
const BASE_URL = 'http://localhost/web_termproject/back';

// 토큰 가져오기 헬퍼
function getToken() {
    return localStorage.getItem('mfp_token');
}

// 로그아웃 로직
function logout() {
    localStorage.removeItem('mfp_token');
    alert('로그아웃 되었습니다.');
    window.location.href = 'login.html';
}

/* 로그인 유저 정보 불러오기 → 버튼에 표시 */
async function loadUserProfile() {
    const token = getToken();
    if (!token) return;

    try {
        const res = await fetch(`${BASE_URL}/request_user_info.php`, {
            method: 'POST',
            headers: {
                "Authorization": `Bearer ${token}`,
                "Content-Type": "application/json"
            }
        });

        const json = await res.json();

        if (!json.success) return;

        const user = json.data;  // ← ★ 여기! json.user 아님, json.data임

        // 버튼 내용 업데이트
        document.querySelector(".gradient-buttons button:nth-child(1)").textContent =
            `이름: ${user.name ?? '-'}`;

        document.querySelector(".gradient-buttons button:nth-child(2)").textContent =
            `키: ${user.height ?? '-'} cm`;

        document.querySelector(".gradient-buttons button:nth-child(3)").textContent =
            `몸무게: ${user.weight ?? '-'} kg`;

    } catch (e) {
        console.error("사용자 정보 로딩 실패:", e);
    }
}


// 상단 로그인/로그아웃 버튼 관리
document.addEventListener("DOMContentLoaded", () => {
    const authLink = document.getElementById('auth-link');
    const token = getToken();
    
    if (token) {
        authLink.textContent = '로그아웃';
        authLink.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
        // 로그인 되었다면 오늘의 데이터 불러오기
        loadDailySummary();
        loadUserProfile();
    } else {
        authLink.textContent = '로그인';
        authLink.href = 'login.html';
    }
});

/* 오늘의 요약 불러오기*/
async function loadDailySummary() {
    try {
        const res = await fetch(`${BASE_URL}/get_daily_summary.php`, {
            headers: { 'Authorization': `Bearer ${getToken()}` }
        });
        const json = await res.json();
        
        if (json.success) {
            // 섭취량 업데이트
            document.getElementById("today-intake-kcal").textContent = json.intake;
            // 운동량 업데이트
            document.getElementById("today-activity-kcal").textContent = json.burned;
            
            // BMI 자동 계산 (DB에 정보가 있다면)
            if (json.user && json.user.height && json.user.weight) {
                const h = json.user.height / 100;
                const bmi = (json.user.weight / (h * h)).toFixed(2);
                document.getElementById("today-bmi").textContent = bmi;
            }
        }
    } catch (e) {
        console.error("요약 불러오기 실패", e);
    }
}

// 식단 저장 버튼 이벤트
document.getElementById('btn-plate-save').addEventListener('click', async () => {
    if (!getToken()) {
        alert("로그인이 필요한 기능입니다.");
        location.href = 'login.html';
        return;
    }
    if (foodState.plate.length === 0) {
        alert("접시에 담긴 음식이 없습니다.");
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}/save_food_log.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify({ plate: foodState.plate })
        });
        const json = await res.json();
        if (json.success) {
            alert("식단이 기록되었습니다!");
            foodState.plate = []; // 접시 비우기
            renderPlate();
            loadDailySummary(); // 상단 요약 갱신
        } else {
            alert(json.message);
        }
    } catch (e) {
        console.error(e);
        alert("저장 중 오류 발생");
    }
});

/* 운동 기록 저장 */
async function saveActivity() {
    if (!getToken()) {
        alert("로그인이 필요한 기능입니다.");
        location.href = 'login.html';
        return;
    }

    // 현재 화면에 계산된 값 가져오기
    const kcal = document.getElementById("activity-value").innerText;
    const select = document.getElementById("activity-select");
    const name = select.options[select.selectedIndex].text; // 운동 이름
    
    // 시간 계산
    const h = parseInt(document.getElementById("activity-hour").value);
    const m = parseInt(document.getElementById("activity-minute").value);
    const duration = h * 60 + m;

    if (kcal == 0 || duration == 0) {
        alert("먼저 운동량을 계산해주세요.");
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}/save_activity_log.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify({ name, duration, kcal })
        });
        const json = await res.json();
        if (json.success) {
            alert("운동이 기록되었습니다!");
            loadDailySummary(); // 상단 요약 갱신
        } else {
            alert(json.message);
        }
    } catch (e) {
        console.error(e);
        alert("저장 오류");
    }
}

const whiteSection = document.getElementById("white-section");

window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
        whiteSection.classList.add("active");
    } else {
        whiteSection.classList.remove("active");
    }
});

/*기초대사량 계산 부분*/
function calculateBMR() {
    const gender = document.getElementById("gender").value;
    const height = Number(document.getElementById("height").value);
    const weight = Number(document.getElementById("weight").value);
    const age = Number(document.getElementById("age").value);

    if (!gender || !height || !weight || !age) {
        alert("모든 값을 입력해주세요!");
        return;
    }
    let bmr = 0;
    if (gender === "male") {
        bmr = 66.47 + (13.75 * weight) + (5 * height) - (6.76 * age);
    } else {
        bmr = 655.1 + (9.56 * weight) + (1.85 * height) - (4.68 * age);
    }
    document.getElementById("bmr-value").textContent = Math.round(bmr);
}

/*BMI 계산부분 */
function calculateBMI() {
    const h = Number(document.getElementById("bmi-height").value);
    const w = Number(document.getElementById("bmi-weight").value);

    if (!h || !w) {
        alert("신장과 체중을 입력해주세요!");
        return;
    }

    const bmi = w / Math.pow(h / 100, 2);
    document.getElementById("bmi-value").textContent = bmi.toFixed(1);

    let status = "";

    if (bmi < 18.5) {
        status = "저체중";
    } else if (bmi < 23) {
        status = "정상 체중";
    } else if (bmi < 25) {
        status = "과체중";
    } else {
        status = "비만";
    }

    document.getElementById("bmi-status-text").textContent = status;
}

// hour dropdown 자동 생성
window.addEventListener("DOMContentLoaded", () => {
    const hourSelect = document.getElementById("activity-hour");
    for (let i = 1; i <= 23; i++) {
        const opt = document.createElement("option");
        opt.value = i;
        opt.textContent = `${i} 시간`;
        hourSelect.appendChild(opt);
    }
});


function calculateActivity() {
    const weight = parseFloat(document.getElementById("activity-weight").value);
    const met = parseFloat(document.getElementById("activity-select").value);

    const hour = parseInt(document.getElementById("activity-hour").value);
    const minute = parseInt(document.getElementById("activity-minute").value);

    // 필수 입력 체크
    if (!weight || weight <= 0) {
        alert("체중을 올바르게 입력해주세요!");
        return;
    }

    if (!met) {
        alert("운동 종목을 선택해주세요!");
        return;
    }

    // minute 입력 검증
    if (isNaN(minute) || minute < 0 || minute > 59) {
        alert("분(minute)은 0~59 사이로 입력해주세요!");
        return;
    }

    const totalMinutes = hour * 60 + minute;

    if (totalMinutes <= 0) {
        alert("운동 시간을 입력해주세요!");
        return;
    }

    // kcal 계산
    const kcal = (0.0175 * met * weight * totalMinutes).toFixed(2);

    document.getElementById("activity-value").innerText = kcal;
    document.getElementById("today-activity-kcal").textContent = kcal;

}


// 음식 칼로리 → 오늘의 섭취량 반영
function addToIntake() {
    const foodKcal = parseFloat(document.getElementById("food-calorie-value").textContent);
    const current = parseFloat(document.getElementById("today-intake-kcal").textContent);

    const updated = current + foodKcal;
    document.getElementById("today-intake-kcal").textContent = updated;
}

/** ================= Nutrition Search + Plate ================== */

// 1) 환경별 API 엔드포인트
/*const NUTRI_API = 'http://localhost/my_fitness_partner/back/search.php'*/; // 🔧 로컬 XAMPP
const NUTRI_API = `${BASE_URL}/search.php`;
// 2) DOM 참조 (기존 HTML id 활용)
const $cat = document.getElementById('food-category'); // raw/processed/meal
const $kwd = document.getElementById('food-search');
const $pageSelect = document.getElementById('page-select'); // (무한 스크롤에선 미사용)
const $foodList = document.getElementById('food-list');
const $foodDetail = document.getElementById('food-detail');
const $plateBody = document.getElementById('plate-body');
const $btnPlateCalc = document.getElementById('btn-plate-calc');
const $calcTotalBox = document.getElementById('calc-total-box');
const $foodSentinel = document.getElementById('food-sentinel');

let rowIdSeq = 1;

// 3) 상태
const foodState = {
  query: { dataCd: 'R', foodNm: '', pageNo: 1, numOfRows: 10 },
  loading: false,
  done: false,
  items: [],
  meta: { total: 0, page: 0, pageSize: 0, category: '' },
  selected: null,
  plate: []
};

// 4) 카테고리 → dataCd 매핑
function toDataCd(v) {
  switch (v) {
    case 'raw': return 'R';
    case 'processed': return 'P';
    case 'meal': return 'D';
    default: return 'R';
  }
}

// 5) 이스케이프
function esc(s) {
  return String(s || '').replace(/[&<>"'`=\/]/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'
  }[c]));
}

// 6) 목록 렌더 (foodNm만)
function renderFoodList(append = true) {
  if (!append) $foodList.innerHTML = '';

  if (foodState.items.length === 0 && !foodState.loading && foodState.meta.total === 0) {
    $foodList.innerHTML = '<div class="empty">검색 결과가 없습니다.</div><div id="food-sentinel"></div>';
    return;
  }

  const frag = document.createDocumentFragment();
  foodState.items.forEach((it, idx) => {
    if (document.getElementById('food-row-' + idx)) return;
    const row = document.createElement('div');
    row.className = 'food-row';
    row.id = 'food-row-' + idx;
    row.innerHTML = `<div class="title">${esc(it.name ?? it.foodNm ?? '(이름없음)')}</div>`;
    row.addEventListener('click', () => renderFoodDetail(it));
    frag.appendChild(row);
  });

  const sentinel = document.getElementById('food-sentinel');
  if (sentinel) $foodList.insertBefore(frag, sentinel);
  else $foodList.appendChild(frag);

  if (foodState.loading) addLoading();
  else { removeLoading(); if (foodState.done) addDone(); }
}

function addLoading() {
  let el = document.getElementById('food-loading');
  if (!el) {
    el = document.createElement('div');
    el.id = 'food-loading';
    el.className = 'loading';
    el.textContent = '불러오는 중...';
    $foodList.appendChild(el);
  }
}

function removeLoading() {
  const el = document.getElementById('food-loading');
  if (el) el.remove();
}

function addDone() {
  let el = document.getElementById('food-done');
  if (!el) {
    el = document.createElement('div');
    el.id = 'food-done';
    el.className = 'empty';
    el.textContent = '모든 결과를 불러왔습니다.';
    $foodList.appendChild(el);
  }
}

// 7) 상세 + 접시에 담기 버튼
function renderFoodDetail(it) {
  foodState.selected = it;
  $foodDetail.innerHTML = `
    <h3 style="margin:4px 0;">${esc(it.name || '(이름없음)')}</h3>
    <div class="muted">ID: ${esc(it.id || '-')}</div>
    <div style="margin-top:8px;">
      <div><b>분류</b> · ${esc(it.hierarchy?.lv3 || '')} / ${esc(it.hierarchy?.lv4 || '')}
        ${it.hierarchy?.lv5 ? ' / ' + esc(it.hierarchy.lv5) : ''} ${it.hierarchy?.lv7 ? ' / ' + esc(it.hierarchy.lv7) : ''}</div>
      <div><b>단위량</b> · ${esc(it.serving?.unit || '100g')}</div>
    </div>
    <div style="margin-top:10px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
      ${pill('열량', nfmt(it.nutrients?.kcal), 'kcal')}
      ${pill('탄수화물', nfmt(it.nutrients?.carb), 'g')}
      ${pill('단백질', nfmt(it.nutrients?.protein), 'g')}
      ${pill('지방', nfmt(it.nutrients?.fat), 'g')}
      ${pill('당류', nfmt(it.nutrients?.sugar), 'g')}
      ${pill('나트륨', nfmt(it.nutrients?.sodium), 'mg')}
    </div>
    <div style="margin-top:12px;">
      <button id="btn-add-plate" class="calc-btn">접시에 담기</button>
    </div>
  `;
  document.getElementById('btn-add-plate').addEventListener('click', () => addToPlate(it));
}

function pill(label, val, unit='') {
  return `<div class="pill" style="background:#f6f6f6;border-radius:10px;padding:8px 10px;text-align:center;">
    <div style="font-size:12px;color:#777">${label}</div>
    <div style="font-weight:700">${val}${unit ? ' ' + unit : ''}</div>
  </div>`;
}

function nfmt(n){ return n==null?'-':Number(n).toFixed(1).replace(/\.0$/,''); }

// 8) 접시에 담기
function addToPlate(item) {
  const unit = item.serving?.unit || '100g';
  const m = String(unit).match(/[\d.]+/);
  const baseWeight = m ? parseFloat(m[0]) : 100;

  const plateItem = {
    rowId: 'r' + (rowIdSeq++),
    id: item.id || null,
    name: item.name || '(이름없음)',
    unit,
    baseWeight,
    weight: baseWeight, // 기본은 기준 단위량
    nutrients: {
      kcal: item.nutrients?.kcal ?? 0,
      carb: item.nutrients?.carb ?? 0,
      protein: item.nutrients?.protein ?? 0,
      fat: item.nutrients?.fat ?? 0
    }
  };
  foodState.plate.push(plateItem);
  renderPlate();
}

// 9) 접시 테이블 렌더 + 중량/삭제
function renderPlate() {
  $plateBody.innerHTML = '';
  if (foodState.plate.length === 0) {
    const tr = document.createElement('tr');
    tr.className = 'empty-row';
    tr.innerHTML = `<td colspan="8" class="muted">접시에 담긴 식품이 없습니다.</td>`;
    $plateBody.appendChild(tr);
    return;
  }
  foodState.plate.forEach(p => {
    const tr = document.createElement('tr');
    tr.dataset.rowId = p.rowId;
    tr.innerHTML = `
      <td class="name">${esc(p.name)}</td>
      <td>${esc(p.unit)}</td>
      <td>${isNum(p.nutrients.kcal) ? Number(p.nutrients.kcal).toFixed(1) : '-'}</td>
      <td>${isNum(p.nutrients.carb) ? Number(p.nutrients.carb).toFixed(1) : '-'}</td>
      <td>${isNum(p.nutrients.protein) ? Number(p.nutrients.protein).toFixed(1) : '-'}</td>
      <td>${isNum(p.nutrients.fat) ? Number(p.nutrients.fat).toFixed(1) : '-'}</td>
      <td>
        <input type="number" class="weight" min="0" step="1" value="${p.weight}" data-row="${p.rowId}" /> g
      </td>
      <td>
        <button class="btn-small" data-del="${p.rowId}">삭제</button>
      </td>
    `;
    $plateBody.appendChild(tr);
  });

  // 중량 변경
  $plateBody.querySelectorAll('input.weight').forEach(inp => {
    inp.addEventListener('input', e => {
      const rowId = e.target.dataset.row;
      const v = parseFloat(e.target.value || '0');
      const target = foodState.plate.find(x => x.rowId === rowId);
      if (target) target.weight = v > 0 ? v : 0;
    });
  });

  // 삭제
  $plateBody.querySelectorAll('button[data-del]').forEach(btn => {
    btn.addEventListener('click', e => {
      const rowId = e.target.dataset.del;
      foodState.plate = foodState.plate.filter(x => x.rowId !== rowId);
      renderPlate();
    });
  });
}

function isNum(v){ return v !== null && v !== '' && !isNaN(v); }

// 10) 계산하기 (가중합)
$btnPlateCalc.addEventListener('click', () => {
  if (foodState.plate.length === 0) {
    $calcTotalBox.textContent = '접시에 담긴 식품이 없습니다.';
    return;
  }
  let total = { kcal: 0, carb: 0, protein: 0, fat: 0 };
  foodState.plate.forEach(p => {
    const factor = p.baseWeight > 0 ? (p.weight / p.baseWeight) : 0;
    total.kcal    += (Number(p.nutrients.kcal) || 0)    * factor;
    total.carb    += (Number(p.nutrients.carb) || 0)    * factor;
    total.protein += (Number(p.nutrients.protein) || 0) * factor;
    total.fat     += (Number(p.nutrients.fat) || 0)     * factor;
  });
  $calcTotalBox.innerHTML = `
    <b>총 영양 성분 (현재 중량 기준)</b><br>
    열량: ${Number(total.kcal).toFixed(1)} kcal<br>
    탄수화물: ${Number(total.carb).toFixed(1)} g<br>
    단백질: ${Number(total.protein).toFixed(1)} g<br>
    지방: ${Number(total.fat).toFixed(1)} g
  `;
});

// 11) 검색 실행 + 무한 스크롤
function resetAndSearch() {
  foodState.items = [];
  foodState.done = false;
  foodState.loading = false;

  const dataCd = toDataCd($cat.value || 'raw');
  const foodNm = ($kwd.value || '').trim();

  if (!foodNm) {
    $foodList.innerHTML = '<div class="empty">검색어를 입력하세요.</div>';
    $foodDetail.innerHTML = '<div class="empty">항목을 선택하면 상세가 표시됩니다.</div>';
    return;
  }
  foodState.query = { dataCd, foodNm, pageNo: 1, numOfRows: 10 };

  $foodList.innerHTML = '<div class="loading">검색 중...</div><div id="food-sentinel"></div>';
  $foodDetail.innerHTML = '<div class="empty">항목을 선택하면 상세가 표시됩니다.</div>';
  fetchFoodPage().then(mountFoodObserver);
}
window.resetAndSearch = resetAndSearch;

async function fetchFoodPage() {
  if (foodState.loading || foodState.done) return;
  foodState.loading = true; addLoading();

  const payload = {
    dataCd: foodState.query.dataCd,
    foodNm: foodState.query.foodNm,
    pageNo: foodState.query.pageNo,
    numOfRows: foodState.query.numOfRows,
    type: 'json'
  };

  let resText = '';
  try {
    const res = await fetch(NUTRI_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    resText = await res.text();
    if (!res.ok) throw new Error(`HTTP ${res.status}: ${resText}`);
    const json = JSON.parse(resText);

    let meta = null, items = null;

    // (A) 표준화 {ok, meta, items}
    if (json && json.ok && json.meta && Array.isArray(json.items)) {
      meta = json.meta;
      items = json.items;

    // (B) 원본 { ok:true, data:{response:{body:{items...}}}}
    } else if (json && json.ok && json.data?.response?.body) {
      const body = json.data.response.body;
      const rawItems = Array.isArray(body.items) ? body.items : [];
      const total = Number(body.totalCount ?? 0);
      const page = Number(body.pageNo ?? foodState.query.pageNo);
      const pageSize = Number(body.numOfRows ?? foodState.query.numOfRows);
      const category = rawItems[0]?.typeNm ||
        (foodState.query.dataCd === 'R' ? '원재료성' : foodState.query.dataCd === 'P' ? '가공식품' : '음식');

      const toNum = v => (v === '' || v == null || isNaN(v)) ? null : Number(v);
      items = rawItems.map(row => ({
        id: row.foodCd ?? null,
        name: row.foodNm ?? row.foodLv4Nm ?? '(이름없음)',
        hierarchy: {
          lv3: row.foodLv3Nm ?? null,
          lv4: row.foodLv4Nm ?? null,
          lv5: row.foodLv5Nm ?? null,
          lv7: row.foodLv7Nm ?? null,
        },
        origin: { code: row.foodOriginCd ?? null, name: row.foodOriginNm ?? null },
        serving: { unit: row.nutConSrtrQua ?? '100g' },
        nutrients: {
          kcal:    toNum(row.enerc),
          protein: toNum(row.prot),
          fat:     toNum(row.fatce),
          carb:    toNum(row.chocdf),
          sugar:   toNum(row.sugar),
          sodium:  toNum(row.nat),
        },
        source: row.srcNm ?? null,
        updatedAt: row.crtrYmd ?? null,
      }));
      meta = { page, pageSize, total, category };

    } else {
      throw new Error('알 수 없는 응답 포맷');
    }

    foodState.meta = meta || {};
    const got = (items || []).length;
    foodState.items = foodState.items.concat(items || []);
    const loaded = foodState.items.length;
    const total = Number(foodState.meta.total || 0);
    foodState.done = (total > 0 ? loaded >= total : got === 0);
    foodState.query.pageNo += 1;

    renderFoodList(true);

  } catch (e) {
    console.error('nutrition fetch error:', e, resText);
    removeLoading();
    const err = document.createElement('div');
    err.className = 'empty';
    err.textContent = '불러오기에 실패했습니다. 콘솔을 확인해주세요.';
    $foodList.appendChild(err);
    foodState.done = true;
  } finally {
    foodState.loading = false;
  }
}

// 무한 스크롤 옵저버
const foodIO = new IntersectionObserver(entries => {
  entries.forEach(entry => { if (entry.isIntersecting) fetchFoodPage(); });
}, { root: $foodList, threshold: 0.1 });

function mountFoodObserver() {
  const s = document.getElementById('food-sentinel');
  if (s) foodIO.observe(s);
}

// 12) 이벤트 바인딩
document.addEventListener('DOMContentLoaded', () => {
  // 페이지 셀렉트는 무한 스크롤과 동시 사용하지 않아 비활성화(원하면 숨기세요)
  if ($pageSelect) { $pageSelect.disabled = true; }

  // Enter로 검색
  $kwd.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      resetAndSearch();
    }
  });
  // 카테고리 변경 시도 검색
  $cat.addEventListener('change', resetAndSearch);
});


// ========= 모달 요소 ========
const modal = document.getElementById("update-modal");
const modalTitle = document.getElementById("modal-title");
const modalInput = document.getElementById("modal-input");
const modalSaveBtn = document.getElementById("modal-save-btn");
const modalCancelBtn = document.getElementById("modal-cancel-btn");

let currentMode = ""; // 'height' 또는 'weight'

// 모달 열기
function openModal(mode, currentValue) {
    currentMode = mode;

    modalTitle.textContent = mode === "height" ? "키 수정" : "몸무게 수정";
    modalInput.value = currentValue || "";
    modalInput.placeholder = mode === "height" ? "키 (cm)" : "몸무게 (kg)";

    modal.style.display = "flex"; // 화면에 표시
}

// 모달 닫기
function closeModal() {
    modal.style.display = "none";
}

// 버튼 클릭 → 모달 띄우기
document.getElementById("btn-user-height").addEventListener("click", () => {
    const current = document.getElementById("btn-user-height").textContent.replace(/\D+/g, "");
    openModal("height", current);
});

document.getElementById("btn-user-weight").addEventListener("click", () => {
    const current = document.getElementById("btn-user-weight").textContent.replace(/\D+/g, "");
    openModal("weight", current);
});

// 취소 버튼
modalCancelBtn.addEventListener("click", closeModal);

// 저장 버튼
modalSaveBtn.addEventListener("click", async () => {
    const value = modalInput.value;
    if (!value) return alert("값을 입력해주세요!");

    const token = getToken();
    if (!token) return alert("로그인이 필요합니다.");

    const res = await fetch(`${BASE_URL}/update_profile.php`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${token}`
        },
        body: JSON.stringify({ mode: currentMode, value })
    });

    const json = await res.json();

    if (json.success) {
        alert("수정 완료!");

        // 화면에 바로 반영
        if (currentMode === "height") {
            document.getElementById("btn-user-height").textContent = `키: ${value} cm`;
        } else {
            document.getElementById("btn-user-weight").textContent = `몸무게: ${value} kg`;
        }

        closeModal();
    } else {
        alert(json.message);
    }
});
