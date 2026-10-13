
const searchInput = document.getElementById("searchInput");
const searchBtn = document.getElementById("searchBtn");
const courseCards = document.querySelectorAll(".course-card");
const noResults = document.getElementById("noResults");

function searchCourses() {
    const keyword = searchInput.value
        .trim()
        .toLocaleLowerCase("vi");

    let found = 0;

    courseCards.forEach(function(card) {
        const courseText = card.textContent
            .toLocaleLowerCase("vi");

        if (courseText.includes(keyword)) {
            card.style.display = "";
            found++;
        } else {
            card.style.display = "none";
        }
    });

    noResults.hidden = found > 0;
}

searchBtn.addEventListener("click", searchCourses);

searchInput.addEventListener("input", searchCourses);
