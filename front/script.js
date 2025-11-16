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


// 기초대사량/BMI 버튼 클릭 →  기초대사량/bmi 로 이동
document.querySelector(".gradient-buttons button:nth-child(1)")
    .addEventListener("click", () => {
        document.getElementById("white-section").scrollIntoView({
            behavior: "smooth"
        });
    });

