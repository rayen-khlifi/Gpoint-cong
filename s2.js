document.addEventListener("DOMContentLoaded", () => {
    const pointageForm = document.getElementById("pointageForm");
    const resultDiv = document.getElementById("result");

    if (pointageForm) {
        pointageForm.addEventListener("submit", function(event) {
            event.preventDefault();

            
            const workerName = document.getElementById("workerName").value.trim();
            const checkInTime = document.getElementById("checkInTime").value;
            const checkOutTime = document.getElementById("checkOutTime").value;

            
            if (!workerName || !checkInTime || !checkOutTime) {
                showResult("Veuillez remplir tous les champs !", "error");
                return;
            }

            
            const [inHours, inMinutes] = checkInTime.split(":").map(Number);
            const [outHours, outMinutes] = checkOutTime.split(":").map(Number);

            
            const totalWorkedMinutes = (outHours * 60 + outMinutes) - (inHours * 60 + inMinutes);
            
            
            if (totalWorkedMinutes <= 0) {
                showResult("L'heure de départ doit être après l'heure d'arrivée !", "error");
                return;
            }

            const totalWorkedHours = Math.floor(totalWorkedMinutes / 60);
            const remainingMinutes = totalWorkedMinutes % 60;

            
            let message = `<strong>Travailleur :</strong> ${workerName}<br>`;
            message += `<strong>Heures travaillées :</strong> ${totalWorkedHours}h ${remainingMinutes}min`;

            
            if (inHours > 7 || (inHours === 7 && inMinutes > 0)) {
                const retardMinutes = (inHours - 7) * 60 + inMinutes;
                const retardHours = Math.floor(retardMinutes / 60);
                const retardMins = retardMinutes % 60;
                
                message += `<br><strong class="retard">Retard :</strong> ${retardHours}h ${retardMins}min après 7h00`;
            }

            
            if (totalWorkedMinutes > 8 * 60) {
                const extraMinutes = totalWorkedMinutes - 8 * 60;
                const extraHours = Math.floor(extraMinutes / 60);
                const extraMins = extraMinutes % 60;
                message += `<br><strong class="extra">Heures supplémentaires :</strong> ${extraHours}h ${extraMins}min`;
            }

            showResult(message, "success");
        });
    }

    function showResult(message, type) {
        resultDiv.innerHTML = `<div class="alert ${type}">${message}</div>`;
    }
});