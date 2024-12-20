import logging
import re
from collections import Counter

import matplotlib.pyplot as plt
import neologdn
from datasets import load_dataset
from tqdm import tqdm
from transformers import WhisperTokenizerFast

CHUNK_SIZE = 1024 * 1024
INPUT_FILE = "D:/newproject/combined_japanese_corpus.txt"
OUTPUT_FILE = "D:/newproject/combined_japanese_corpus2.txt"
TOKENIZER_SAVE_PATH = "D:/newproject/tokenizers/new/new_tokenizer"
NEW_TOKENIZER_SAVE_PATH = "D:/newproject/new_tokenizer2"
NEW_TOKENIZER_JSON_PATH = "D:/newproject/new_tokenizer.json"
BATCH_SIZE = 100
NUM_SHARDS = 2000
VOCAB_SIZE = 51865

# Logging setup
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

def custom_normalize(text):
    text = neologdn.normalize(text, repeat=1)
    text = re.sub(r'[\r\n\t\s]+', '', text)
    text = re.sub(r'[A-Za-z]+', '', text)
    text = re.sub(r'[0-9]+', '', text)
    text = re.sub(r'[!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~、。！？：；「」（）【】『』〈〉《》〔〕［］｛｝]', '', text)  # Expanded punctuation
    text = ''.join([char for char in text if re.match(r'[ぁ-んァ-ン一-龥]', char)])
    return text

def data_generator(dataset, batch_size):
    batch = []
    for i, example in enumerate(iterable=dataset):
        if bool(example['text']):
            normalized_text = custom_normalize(example['text'])
            batch.append(normalized_text)
            if len(batch) == batch_size:
                yield batch
                batch = []
    if batch:
        yield batch

def dataset_generator(dataset):
    for chunk in tqdm(dataset, desc="Processing dataset"):
        for text in chunk["text"]:
            yield text

if __name__ == "__main__":
    logging.info("Starting text cleaning and tokenizer training process.")

    try:
        with open(INPUT_FILE, mode='r', encoding='utf-8') as infile, open(OUTPUT_FILE, 'w', encoding='utf-8') as outfile:
            while True:
                chunk = infile.read(CHUNK_SIZE)
                if not chunk:
                    break

                if chunk[-1] != '\n':
                    remainder = infile.readline()
                    chunk += remainder

                processed_chunk = '\n'.join(custom_normalize(text=line) for line in chunk.splitlines())

                outfile.write(processed_chunk + '\n')
        logging.info("Text cleaning and saving completed.")

    except FileNotFoundError:
        logging.error(f"File not found: {INPUT_FILE}")
        exit()
    except IOError as e:
        logging.error(f"IOError during file processing: {e}")
        exit()

    data = OUTPUT_FILE
    dataset = load_dataset(path="text", data_files=data)["train"].to_iterable_dataset(num_shards=NUM_SHARDS)

    tokenizer = WhisperTokenizerFast.from_pretrained(pretrained_model_name_or_path=TOKENIZER_SAVE_PATH)
    special_tokens = [
        "<CRYING>", "<SINGING>", "<LAUGHING>", "<APPLAUSE>", "<MUSIC>", "<PAD>", "<UNK>", "<BOS>", "<EOS>", "<MASK>",
        "<NOISE>", "<CLS>", "<END>", "<START>"
    ]
    tokenizer.add_special_tokens({'additional_special_tokens': special_tokens})

    generator = data_generator(dataset=dataset, batch_size=BATCH_SIZE)
    oov_count = Counter()
    total_count = 0

    for batch in generator:
        tokens = [tokenizer.tokenize(text) for text in batch]
        oov_count.update([token for sublist in tokens for token in sublist if token == "<|endoftext|>"])
        total_count += sum(len(sublist) for sublist in tokens)

    oov_rate = (sum(oov_count.values()) / total_count) * 100
    logging.info(f"OOV Rate: {oov_rate:.2f}%")

    token_counts = Counter([token for sublist in tokens for token in sublist])
    most_common_tokens = token_counts.most_common(n=10)

    tokens, counts = zip(*most_common_tokens)
    plt.bar(x=tokens, height=counts)
    plt.xlabel(xlabel='Tokens')
    plt.ylabel(ylabel='Frequency')
    plt.title(label='Top 10 Tokens Frequency Distribution')
    plt.xticks(rotation=90)
    plt.tight_layout()
    plt.show()

    try:
        new_tokenizer = tokenizer.train_new_from_iterator(
            dataset_generator(dataset),
            vocab_size=VOCAB_SIZE,
            length=len(dataset),
            # dropout=0.1,  
        )
        new_tokenizer.save_pretrained(NEW_TOKENIZER_SAVE_PATH)
        new_tokenizer.save(NEW_TOKENIZER_JSON_PATH)
        logging.info(f"New tokenizer trained and saved to {NEW_TOKENIZER_SAVE_PATH} and {NEW_TOKENIZER_JSON_PATH}")

    except Exception as e:
        logging.error(f"Error during tokenizer training: {e}")
        exit()

    logging.info("Process completed.")
