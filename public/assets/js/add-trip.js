function nextStep(step) {
    // Versteckt den aktuellen Schritt
    document.getElementById('step-' + step).style.display = 'none';

    // Zeigt den nächsten Schritt an
    var nextStep = step + 1;
    document.getElementById('step-' + nextStep).style.display = 'block';
}

function addStopover() {
    var stopoverInput = document.getElementById('stopover-input');
    var stopoverValue = stopoverInput.value.trim();

    if (stopoverValue !== "") {
        // Erstellen eines Listenelements für den Zwischenstopp
        var listItem = document.createElement('li');
        listItem.textContent = stopoverValue;

        // Erstellen des "Löschen"-Buttons
        var deleteButton = document.createElement('button');
        deleteButton.textContent = 'Löschen';
        deleteButton.onclick = function() {
            listItem.remove(); // Löscht den Zwischenstopp
        };

        // Füge den "Löschen"-Button dem Listenelement hinzu
        listItem.appendChild(deleteButton);

        // Füge das Listenelement zur Liste hinzu
        document.getElementById('stopover-list').appendChild(listItem);

        // Setze das Eingabefeld zurück
        stopoverInput.value = "";
    }
}

// JavaScript zum Hinzufügen eines Zwischenstopps
document.getElementById('add_stopover').addEventListener('click', function() {
var stopoversContainer = document.querySelector('form div');
var stopoverCount = document.querySelectorAll('.stopover').length;

var newStopoverHTML = `
    <div class="stopover">
        <label for="stopover_${stopoverCount}">Zwischenstopp ${stopoverCount + 1}:</label>
        <input type="text" id="stopover_${stopoverCount}" name="stopovers[${stopoverCount}]">
        <select name="transport[${stopoverCount}]">
            <option value="Zug">Zug</option>
            <option value="Auto">Auto</option>
            <option value="Flugzeug">Flugzeug</option>
            <option value="Schiff">Schiff</option>
        </select>
    </div>
`;
stopoversContainer.insertAdjacentHTML('beforeend', newStopoverHTML);
});

    // Funktion, um Unterkunftseinträge zu erstellen
function generateAccommodationEntries() {
    var stopovers = document.querySelectorAll('#stopover-list li'); // Alle Zwischenstopps
    var accommodationContainer = document.getElementById('accommodation-container');
    var accommodationList = document.getElementById('accommodation-list');

    var totalNights = 0; // Gesamtzahl der Nächte
    var nightsLeft = calculateNightsLeft(); // Berechnet die verbleibenden Nächte bis zum Enddatum

    // Unterkunft für Startpunkt
    createAccommodationEntry('Startpunkt', accommodationContainer, accommodationList, totalNights);

    // Unterkunft für Zwischenstopps
    stopovers.forEach(function(stopover, index) {
        createAccommodationEntry('Zwischenstopp ' + (index + 1), accommodationContainer, accommodationList, totalNights);
    });

    // Unterkunft für Hauptziel
    createAccommodationEntry('Hauptziel', accommodationContainer, accommodationList, totalNights);

    // Anzeige der verbleibenden Nächte
    document.getElementById('nights-left').textContent = 'Verbleibende Nächte bis zum Enddatum: ' + nightsLeft;
}

// Funktion zum Erstellen eines Unterkunftseintrags für einen Ort
function createAccommodationEntry(location, container, list, totalNights) {
    var div = document.createElement('div');
    div.classList.add('accommodation-entry');

    var label = document.createElement('label');
    label.textContent = 'Hotel für ' + location;

    var hotelNameInput = document.createElement('input');
    hotelNameInput.type = 'text';
    hotelNameInput.name = location.toLowerCase() + '_hotel';
    hotelNameInput.placeholder = 'Hotelname';

    var nightsInput = document.createElement('input');
    nightsInput.type = 'number';
    nightsInput.name = location.toLowerCase() + '_nights';
    nightsInput.placeholder = 'Nächte';
    nightsInput.min = 1;
    nightsInput.addEventListener('input', function() {
        totalNights = calculateTotalNights(); // Update Gesamtzahl der Nächte
        updateAccommodationSummary(totalNights); // Aktualisieren der Unterkunftszusammenfassung
    });

    div.appendChild(label);
    div.appendChild(hotelNameInput);
    div.appendChild(nightsInput);

    // Unterkunftseintrag zu den Eingabefeldern hinzufügen
    container.appendChild(div);

    // Unterkunftseintrag zur Liste der Unterkunftszusammenfassungen hinzufügen
    var listItem = document.createElement('li');
    listItem.textContent = location + ': - Hotel: ' + hotelNameInput.value + ', Nächte: ' + nightsInput.value;
    list.appendChild(listItem);
}

// Funktion zur Berechnung der verbleibenden Nächte bis zum Enddatum
function calculateNightsLeft() {
    var startDate = new Date(document.getElementById('start_date').value); // Startdatum
    var endDate = new Date(document.getElementById('end_date').value); // Enddatum
    var diffTime = endDate - startDate;
    var diffDays = diffTime / (1000 * 3600 * 24); // Differenz in Tagen
    return diffDays; // Zurückgeben der verbleibenden Tage
}

// Funktion zur Berechnung der Gesamtzahl der Nächte
function calculateTotalNights() {
    var nightInputs = document.querySelectorAll('input[name$="_nights"]');
    var totalNights = 0;
    nightInputs.forEach(function(input) {
        totalNights += parseInt(input.value) || 0; // Summieren der Nächte
    });
    return totalNights;
}

// Funktion zur Aktualisierung der Unterkunftszusammenfassung
function updateAccommodationSummary(totalNights) {
    var accommodationList = document.getElementById('accommodation-list');
    var items = accommodationList.querySelectorAll('li');
    items.forEach(function(item) {
        item.textContent = item.textContent.replace(/Nächte: \d+/, 'Nächte: ' + totalNights); // Update der Nächte
    });
}


// Array für Orte und deren Tage
var locations = [];
var activities = [];

// Funktion, um die Tabs für Orte und Tage zu erstellen
function generateLocationTabs() {
    var stopovers = document.querySelectorAll('#stopover-list li'); // Alle Zwischenstopps
    var locationTabs = document.getElementById('location-tabs');
    var dayTabs = document.getElementById('day-tabs');

    // Die Anzahl der Tage für jedes Ziel berechnen
    stopovers.forEach(function(stopover, index) {
        var locationName = stopover.textContent.trim();
        locations.push({
            name: locationName,
            days: calculateDaysForLocation(locationName)
        });

        // Erstelle Tab für Ort
        var locationTab = document.createElement('div');
        locationTab.classList.add('tab');
        locationTab.textContent = locationName;
        locationTab.setAttribute('data-location', locationName);
        locationTab.addEventListener('click', function() {
            showLocationDays(locationName);
        });
        locationTabs.appendChild(locationTab);

        // Erstelle Tabs für Tage des Ortes
        var dayTabContainer = document.createElement('div');
        dayTabContainer.classList.add('day-tabs');
        for (var i = 1; i <= locations[index].days; i++) {
            var dayTab = document.createElement('div');
            dayTab.classList.add('tab');
            dayTab.textContent = 'Tag ' + i;
            dayTab.setAttribute('data-day', i);
            dayTab.setAttribute('data-location', locationName);
            dayTab.addEventListener('click', function() {
                showActivitiesForDay(locationName, parseInt(this.getAttribute('data-day')));
            });
            dayTabContainer.appendChild(dayTab);
        }
        dayTabs.appendChild(dayTabContainer);
    });
}

// Funktion zur Berechnung der Tage für jedes Ziel
function calculateDaysForLocation(location) {
    // Beispiel: Berechnung der Tage, z.B. basierend auf den eingegebenen Nächten
    // Diese Funktion muss später durch echte Daten ersetzt werden
    return 3; // Beispiel: für jeden Ort 3 Tage
}

// Funktion zur Anzeige der Tage für einen Ort
function showLocationDays(locationName) {
    var dayTabs = document.querySelectorAll('.day-tabs');
    dayTabs.forEach(function(dayTab) {
        if (dayTab.getAttribute('data-location') === locationName) {
            dayTab.style.display = 'block'; // Zeigt die Tage des ausgewählten Ortes
        } else {
            dayTab.style.display = 'none'; // Versteckt die Tage für andere Orte
        }
    });
}

// Funktion, um die Aktivitäten für einen Tag anzuzeigen
function showActivitiesForDay(locationName, day) {
    var activityList = document.getElementById('activity-list');
    activityList.innerHTML = ''; // Liste der Aktivitäten zurücksetzen

    // Füge hier Aktivitäten für den Tag hinzu (diese können später aus der Datenbank oder Formulareingabe stammen)
    activities.forEach(function(activity) {
        if (activity.location === locationName && activity.day === day) {
            var activityItem = document.createElement('div');
            activityItem.classList.add('activity');
            activityItem.textContent = activity.title + ' um ' + activity.time + ' - ' + activity.description + ' - Preis: ' + activity.price;
            activityList.appendChild(activityItem);
        }
    });

    // Hinzufügen eines neuen Aktivitätsformulars
    addActivityForm(locationName, day);
}

// Funktion, um ein Aktivitätsformular für den aktuellen Tag hinzuzufügen
function addActivityForm(locationName, day) {
    var activityForm = document.createElement('div');
    activityForm.classList.add('activity-form');

    var titleLabel = document.createElement('label');
    titleLabel.textContent = 'Aktivitätstitel:';
    var titleInput = document.createElement('input');
    titleInput.type = 'text';
    titleInput.name = 'activity_title';

    var timeLabel = document.createElement('label');
    timeLabel.textContent = 'Uhrzeit:';
    var timeInput = document.createElement('input');
    timeInput.type = 'time';
    timeInput.name = 'activity_time';

    var descriptionLabel = document.createElement('label');
    descriptionLabel.textContent = 'Beschreibung:';
    var descriptionInput = document.createElement('textarea');
    descriptionInput.name = 'activity_description';

    var priceLabel = document.createElement('label');
    priceLabel.textContent = 'Preis:';
    var priceInput = document.createElement('input');
    priceInput.type = 'number';
    priceInput.name = 'activity_price';

    var submitButton = document.createElement('button');
    submitButton.textContent = 'Aktivität speichern';
    submitButton.type = 'button';
    submitButton.addEventListener('click', function() {
        var activity = {
            location: locationName,
            day: day,
            title: titleInput.value,
            time: timeInput.value,
            description: descriptionInput.value,
            price: priceInput.value
        };
        activities.push(activity);
        showActivitiesForDay(locationName, day); // Aktualisiere die Aktivitätenliste
    });

    activityForm.appendChild(titleLabel);
    activityForm.appendChild(titleInput);
    activityForm.appendChild(timeLabel);
    activityForm.appendChild(timeInput);
    activityForm.appendChild(descriptionLabel);
    activityForm.appendChild(descriptionInput);
    activityForm.appendChild(priceLabel);
    activityForm.appendChild(priceInput);
    activityForm.appendChild(submitButton);

    var activityList = document.getElementById('activity-list');
    activityList.appendChild(activityForm);
}

// Array für die Budgetposten
var budgetItems = [];

// Funktion zum Hinzufügen eines neuen Budgetpostens
function addBudgetItem() {
// Werte aus dem Formular holen
var category = document.getElementById('category').value;
var description = document.getElementById('description').value;
var amount = parseFloat(document.getElementById('amount').value);

if (!category || !description || isNaN(amount)) {
alert('Bitte füllen Sie alle Felder aus.');
return;
}

// Füge neuen Budgetposten hinzu
var budgetItem = {
category: category,
description: description,
amount: amount
};

budgetItems.push(budgetItem);
updateBudgetOverview();
displayBudgetItems();
}

// Funktion zum Aktualisieren der Budgetübersicht
function updateBudgetOverview() {
var estimatedTotal = 0;
var actualTotal = 0;

// Berechne das Gesamtbudget
budgetItems.forEach(function(item) {
estimatedTotal += item.amount;
// Wir gehen davon aus, dass das tatsächliche Budget anfangs mit dem geschätzten übereinstimmt
actualTotal += item.amount; // Dies könnte später geändert werden, wenn tatsächliche Ausgaben eingegeben werden
});

// Zeige das Budget an
document.getElementById('estimated-total').textContent = `$${estimatedTotal.toFixed(2)}`;
document.getElementById('actual-total').textContent = `$${actualTotal.toFixed(2)}`;
document.getElementById('budget-difference').textContent = `$${(actualTotal - estimatedTotal).toFixed(2)}`;
}

// Funktion zum Anzeigen der Budgetposten
function displayBudgetItems() {
var budgetItemsContainer = document.getElementById('budget-items');
budgetItemsContainer.innerHTML = ''; // Leere den Container

budgetItems.forEach(function(item, index) {
var budgetItemDiv = document.createElement('div');
budgetItemDiv.classList.add('budget-item');
budgetItemDiv.innerHTML = `
    <p><strong>Kategorie:</strong> ${item.category}</p>
    <p><strong>Beschreibung:</strong> ${item.description}</p>
    <p><strong>Betrag:</strong> $${item.amount.toFixed(2)}</p>
    <button type="button" onclick="removeBudgetItem(${index})">Entfernen</button>
`;
budgetItemsContainer.appendChild(budgetItemDiv);
});
}

// Funktion zum Entfernen eines Budgetpostens
function removeBudgetItem(index) {
budgetItems.splice(index, 1);
updateBudgetOverview();
displayBudgetItems();
}

// Array für allgemeine To-Dos
var generalTodos = [];

// Array für Ortspezifische To-Dos
var locationTodos = {};

// Funktion zum Hinzufügen eines neuen allgemeinen To-Dos
function addGeneralTodo() {
var title = document.getElementById('todo-title').value;
var deadline = document.getElementById('todo-deadline').value;
var description = document.getElementById('todo-description').value;
var tags = document.getElementById('todo-tags').value;

if (!title || !deadline || !description) {
alert('Bitte füllen Sie alle Pflichtfelder aus.');
return;
}

var todo = {
title: title,
deadline: deadline,
description: description,
tags: tags.split(',').map(tag => tag.trim())
};

generalTodos.push(todo);
displayGeneralTodos();
resetGeneralTodoForm();
}

// Funktion zum Anzeigen der allgemeinen To-Dos
function displayGeneralTodos() {
var container = document.getElementById('general-todos');
container.innerHTML = '';

generalTodos.forEach(function(todo, index) {
var div = document.createElement('div');
div.classList.add('todo-item');
div.innerHTML = `
    <p><strong>Titel:</strong> ${todo.title}</p>
    <p><strong>Deadline:</strong> ${todo.deadline}</p>
    <p><strong>Beschreibung:</strong> ${todo.description}</p>
    <p><strong>Tags:</strong> ${todo.tags.join(', ')}</p>
    <button onclick="removeGeneralTodo(${index})">Entfernen</button>
`;
container.appendChild(div);
});
}

// Funktion zum Entfernen eines allgemeinen To-Dos
function removeGeneralTodo(index) {
generalTodos.splice(index, 1);
displayGeneralTodos();
}

// Funktion zum Zurücksetzen des Formulars für allgemeine To-Dos
function resetGeneralTodoForm() {
document.getElementById('todo-title').value = '';
document.getElementById('todo-deadline').value = '';
document.getElementById('todo-description').value = '';
document.getElementById('todo-tags').value = '';
}

// Funktion zum Hinzufügen eines Ortspezifischen To-Dos
function addLocationTodo() {
var location = document.getElementById('todo-location').value;
var title = document.getElementById('location-todo-title').value;
var deadline = document.getElementById('location-todo-deadline').value;
var description = document.getElementById('location-todo-description').value;
var tags = document.getElementById('location-todo-tags').value;

if (!location || !title || !deadline || !description) {
alert('Bitte füllen Sie alle Pflichtfelder aus.');
return;
}

if (!locationTodos[location]) {
locationTodos[location] = [];
}

var todo = {
title: title,
deadline: deadline,
description: description,
tags: tags.split(',').map(tag => tag.trim())
};

locationTodos[location].push(todo);
displayLocationTodos(location);
resetLocationTodoForm();
}

// Funktion zum Anzeigen der Ortspezifischen To-Dos für einen bestimmten Ort
function displayLocationTodos(location) {
var container = document.getElementById('location-todos');
container.innerHTML = '';

locationTodos[location].forEach(function(todo, index) {
var div = document.createElement('div');
div.classList.add('todo-item');
div.innerHTML = `
    <p><strong>Titel:</strong> ${todo.title}</p>
    <p><strong>Deadline:</strong> ${todo.deadline}</p>
    <p><strong>Beschreibung:</strong> ${todo.description}</p>
    <p><strong>Tags:</strong> ${todo.tags.join(', ')}</p>
    <button onclick="removeLocationTodo('${location}', ${index})">Entfernen</button>
`;
container.appendChild(div);
});
}

// Funktion zum Entfernen eines Ortspezifischen To-Dos
function removeLocationTodo(location, index) {
locationTodos[location].splice(index, 1);
displayLocationTodos(location);
}

// Funktion zum Zurücksetzen des Formulars für Ortspezifische To-Dos
function resetLocationTodoForm() {
document.getElementById('location-todo-title').value = '';
document.getElementById('location-todo-deadline').value = '';
document.getElementById('location-todo-description').value = '';
document.getElementById('location-todo-tags').value = '';
}

// Zeige das Formular für Ortspezifische To-Dos an, wenn ein Ort ausgewählt wurde
document.getElementById('todo-location').addEventListener('change', function() {
var location = this.value;
if (location) {
document.getElementById('add-location-todo-form').style.display = 'block';
displayLocationTodos(location);
} else {
document.getElementById('add-location-todo-form').style.display = 'none';
}
});

// Diese Funktion wird beim Laden der Seite aufgerufen, um die Transportarten zu generieren
document.addEventListener('DOMContentLoaded', function() {
    generateTransportOptions();
    generateAccommodationEntries();
    generateLocationTabs();
    updateBudgetOverview();
});