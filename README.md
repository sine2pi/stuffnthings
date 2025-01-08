previews.py : python previews.py <video_directory> <trailers_directory> [num_snippets] [snippet_duration] [snippet_interval]

Recursively iterates through directories and creates previews from video files. For example, it can create an x second mp4 av1 preview of x, x second snippets of a movie in a directory that has x number of parts to it. 
Example.. you have a movie in a directory that has 3 parts you can set it to make 1 preview mp4 of the 3 parts with 4 snippets at 0.8 seconds each from each part.. or whatever combo you want.. and then it goes through all the directories and does the same thing .. as long as base name is the same for the movie parts it will auto group thnem. It will skip over ones its already done.
