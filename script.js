document.addEventListener("DOMContentLoaded", () => {
    fetch("getBicycles.php")
        .then(response => response.json())
        .then(data => {
            const bikeList = document.getElementById("bike-list");
            data.forEach(bike => {
                const bikeElement = document.createElement("div");
                bikeElement.textContent = `${bike.name} - ${bike.model} - Size: ${bike.size}`;
                bikeList.appendChild(bikeElement);
            });
        });
});