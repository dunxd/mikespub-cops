function postRefresh()
{
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    hash = window.location.hash.replace("#", "");
    var elmnt = document.getElementById(hash);
    if (elmnt) elmnt.scrollIntoView();
}

function reverseSortEntries(){
    currentData.entries.reverse();
    result = templateMain(currentData);
    $('#main').html(result);
}