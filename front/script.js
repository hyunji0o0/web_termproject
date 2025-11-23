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


// 기초대사량/BMI 버튼 클릭 →  기초대사량/bmi 로 이동
document.querySelector(".gradient-buttons button:nth-child(1)")
    .addEventListener("click", () => {
        document.getElementById("bmi-section").scrollIntoView({
            behavior: "smooth"
        });
    });

// 식품영양성분 계산 버튼 클릭 → 영양성분 계산으로 이동
document.querySelector(".gradient-buttons button:nth-child(2)")
    .addEventListener("click", () => {
        document.getElementById("food-section").scrollIntoView({
            behavior: "smooth"
        });
    });

// 활동대사량 버튼 클릭 →  활동대사량 계산기로 이동
document.querySelector(".gradient-buttons button:nth-child(3)")
    .addEventListener("click", () => {
        document.getElementById("activity-section").scrollIntoView({
            behavior: "smooth"
        });
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

//영양성분-페이지 고르기에서 페이지 늘리는 함수
function createPageOptions(maxPage = 10) {
    const pageSelect = document.getElementById("page-select");
    pageSelect.innerHTML = "";

    const defaultOpt = document.createElement("option");
    defaultOpt.value = "";
    defaultOpt.textContent = "- 선택 -";
    pageSelect.appendChild(defaultOpt);

    for (let i = 1; i <= maxPage; i++) {
        const opt = document.createElement("option");
        opt.value = i;
        opt.textContent = `${i}`;
        pageSelect.appendChild(opt);
    }
}

// 페이지 개수 설정 (원하는 갯수 만큼 입력하세요)
createPageOptions(10);

// 음식 칼로리 → 오늘의 섭취량 반영
function addToIntake() {
    const foodKcal = parseFloat(document.getElementById("food-calorie-value").textContent);
    const current = parseFloat(document.getElementById("today-intake-kcal").textContent);

    const updated = current + foodKcal;
    document.getElementById("today-intake-kcal").textContent = updated;
}

