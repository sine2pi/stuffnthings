import os
import subprocess
import sys
import re

def create_preview(video_path, trailers_dir, num_snippets=5, snippet_duration=0.8, snippet_interval=180):
    output_filename = os.path.splitext(os.path.basename(video_path))[0] + 'T.mp4'
    output_path = os.path.join(trailers_dir, output_filename)

    if os.path.exists(output_path):
        print(f"Skipping {video_path} as {output_filename} already exists in {trailers_dir}.")
        return

    commands = []
    for i in range(num_snippets):
        start_time = 180 + i * snippet_interval
        command = [
            'ffmpeg', '-ss', str(start_time), '-i', video_path, '-t', str(snippet_duration),
            '-vf', 'crop=iw/2:ih:iw/2:0,scale=720:720', '-c:v', 'libx265', '-cq', '35', '-b:v', '0', f'snippet_{i}.mp4'
        ]
        commands.append(command)

    for command in commands:
        subprocess.run(command, check=True)

    snippet_files = [f'snippet_{i}.mp4' for i in range(num_snippets)]

    with open('concat_list.txt', 'w') as f:
        for file in snippet_files:
            f.write(f"file '{file}'\n")
    
    concat_command = [
        'ffmpeg', '-f', 'concat', '-safe', '0', '-i', 'concat_list.txt', '-c:v', 'libx265',
        '-cq', '30', '-b:v', '0', output_path
    ]

    subprocess.run(concat_command, check=True)

    for file in snippet_files:
        os.remove(file)
    os.remove('concat_list.txt')

def process_directory(directory, trailers_dir, num_snippets=5, snippet_duration=0.8, snippet_interval=180):
    processed_basenames = set()

    for root, _, files in os.walk(directory, followlinks=True):
        for filename in files:
            if filename.lower().endswith(".mp4"):
                base_match = re.match(r"\[(.*?)]", filename)
                if base_match:
                    base_name = base_match.group(1)
                    if base_name in processed_basenames:
                        continue

                    if "-A" in filename or not re.search(r"-[A-Z]\.mp4", filename):  # Process part A or single part
                        video_path = os.path.join(root, filename)
                        create_preview(video_path, trailers_dir, num_snippets, snippet_duration, snippet_interval)
                        processed_basenames.add(base_name)

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python create_preview.py <directory> <trailers_dir> [num_snippets] [snippet_duration] [snippet_interval]")
        sys.exit(1)

    directory = sys.argv[1]
    trailers_dir = sys.argv[2]
    num_snippets = int(sys.argv[3]) if len(sys.argv) > 3 else 5
    snippet_duration = float(sys.argv[4]) if len(sys.argv) > 4 else 0.8
    snippet_interval = int(sys.argv[5]) if len(sys.argv) > 5 else 180

    process_directory(directory, trailers_dir, num_snippets, snippet_duration, snippet_interval)
