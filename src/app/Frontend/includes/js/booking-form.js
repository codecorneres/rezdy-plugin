var select = document.getElementById('colorName');
    
function myFunction(event) {
    var color = 'No color selected';

    if (select.selectedIndex > 0) {
        color = select.item(select.selectedIndex).textContent;
    }
    
    document.getElementById('myText').value = color;
}

select.addEventListener('click', myFunction);
myFunction();


// Get references to the select elements
const selectNumber = document.getElementById('select-number');
const selectDate = document.getElementById('select-date');

// Define date options for each number
const dateOptions = {
    1: ['Date 1'],
    2: ['Date 1', 'Date 2'],
    3: ['Date 1', 'Date 2', 'Date 3'],
};

// Event listener to update date options when the number is changed
selectNumber.addEventListener('change', function () {
    const selectedNumber = selectNumber.value;
    
    // Clear existing date options
    selectDate.innerHTML = '';

    // Populate selectDate with options based on the selected number
    dateOptions[selectedNumber].forEach((date, index) => {
        const option = document.createElement('option');
        option.value = 'date${index + 1}';
        option.textContent = date;
        selectDate.appendChild(option);
    });
});
