const main = () => {
    const theURL = 'https://adventure-blog.ddns.net/adventure-blogs-api/get_posts.php';
    const outRec = document.querySelector('#theresult');


    fetch(theURL)
    .then(response => response.text())
    .then(data => {
        let posts = JSON.parse(data);
        let postList = posts.posts;
        postList.forEach(post => {
            let tags = '';
            let postTags = post.tags;
            Object.values(postTags).forEach(tag => {
                tags += `#${tag} `;
            });
            let files = '';
            let postFiles = post.files;
            Object.values(postFiles).forEach(file => {
                files += `<img src="https://adventure-blog.ddns.net/${file.file_path}" alt="Image" height="200">`;
            });
            let location = post.location.name && post.location.name !== 'null' ? `Location: ${post.location.name}<br>` : '';
            tags = tags && tags !== 'null' ? `${tags}<br>` : '';
            let content = post.content && post.content !== 'null' ? `${post.content}<br>` : '';


            outRec.innerHTML += `
            ${post.title}<br>
            By: ${post.username}<br>
            ${post.created_at}<br>
            ${tags}
            ${location}
            ${content}
            ${files}<br>
            <br>`;
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });
    

}




addEventListener('load', main);

