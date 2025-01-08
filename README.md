previews.py : python previews.py <video_directory> [num_snippets] [snippet_duration] [snippet_interval]
or just save in base directory and use python preview.py ./  
Follows symlinks but saves preview in your base directory

Recursively iterates through directories and creates previews from video files. For example, it can create an x second mp4 av1 preview of x, x second snippets of a movie in a directory that has x number of parts to it. 
Example.. you have a movie in a directory that has 3 parts you can set it to make 1 preview mp4 of the 3 parts with 4 snippets at 0.8 seconds each from each part.. or whatever combo you want.. and then it goes through all the directories and does the same thing .. as long as base name is the same for the movie parts it will auto group thnem. It will skip over ones its already done.

mp4_to_dataset.py: all in one script to turn a movie with srt into hundreds of audio samples with transcripts.

new_token.py : give an old tokenizer a new vocab

preproccessing.py : rips an audio dataset from hugging face on the fly turning it into mp3/wavs with transcript .. also processes out silence from audio and bad transcripts etc etc. every dataset on hugging face ie common voice has bad samples in the training set.
