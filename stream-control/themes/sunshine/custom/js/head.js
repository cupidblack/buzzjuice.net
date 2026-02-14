/* 
Add here your JavaScript Code. 
Note. the code entered here will be added in <head> tag 


	Example: 

	var x, y, z; 
	x = 5; d asd asd asd 
	y = 6; 
	z = x + y;

*/

document.addEventListener('click', function (event) {
    // Check if the clicked element is a link with ?link1=wallet or ?link1=show_fund
    if (event.target.matches('a[data-ajax^="?link1=wallet"]') || event.target.matches('a[data-ajax^="?link1=show_fund"]')) {
        event.preventDefault(); // Prevent the default link behavior

        // Get the current href of the link
        const originalUrl = event.target.getAttribute('href');

        // Generate a unique nocache parameter
        const uniqueParam = 'nocache=' + new Date().getTime();

        // Append the nocache parameter to the URL
        const freshUrl = originalUrl.includes('?')
            ? originalUrl + '&' + uniqueParam
            : originalUrl + '?' + uniqueParam;

        // Redirect to the updated URL
        window.location.href = freshUrl;
    }
});
