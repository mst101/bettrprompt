# Podcast Analyzer and Summarizer Suite

A comprehensive system of four interconnected workflows that scrapes podcast information from a specific website, transcribes the audio, analyzes the content, and generates formatted HTML pages with summaries and transcripts.

## Overview

This system consists of four workflows that work sequentially:

1. **Podcast Scraper** - Extracts podcast metadata from the specified website
2. **Transcript Generator** - Downloads and transcribes audio files
3. **Summary Generator** - Analyzes transcripts to extract key learnings and entities
4. **HTML Generator** - Creates formatted HTML pages to display the results

## Workflow 1: Podcast Scraper

### Invocation
- Manually triggered by pressing the "Run" button
- No launch variables required

### System Prompt Outline
- Define the workflow as a podcast metadata scraper
- Specify the elements to extract (episode number, date, title, URL)
- Provide instructions for handling iframes and extracting URLs from them
- Define the expected output format

### Workflow Steps
1. **`start`** - Manual invocation
2. **`userMessage`** - Display welcome message explaining the workflow's purpose
3. **`scrapeUrl`** - Scrape the podcast website at https://ggapps.co.uk/podcast/
4. **`userMessage`** - Parse HTML to extract podcast metadata with structured JSON output
5. **`userMessage`** - Format the extracted data for user review
6. **`end`** - Store the results in a global variable and inform the user about next steps

### Variables
```typescript
interface PodcastScraperVariables {
  // Input
  websiteContent: string;  // Raw HTML from the website (large HTML string)
  
  // Output
  podcastMetadata: Array<{
    episodeNumber: string;  // e.g., "1"
    date: string;           // e.g., "2022-04-15"
    title: string;          // e.g., "Introduction to Podcast Series"
    audioUrl: string;       // e.g., "https://traffic.libsyn.com/secure/8e658e4a-8560-4204-9ebd-6f1db1b6ade2/Ep1.mp3"
  }>;

  // Global output for next workflow
  global.podcastData: Array<{
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
  }>;
}
```

### Custom Functions
None required for this workflow.

## Workflow 2: Transcript Generator

### Invocation
- Manually triggered by pressing the "Run" button
- No launch variables required

### System Prompt Outline
- Define the workflow as a podcast audio transcriber
- Specify the task of downloading and transcribing each podcast
- Explain the importance of speaker identification
- Define the expected output format for transcripts

### Workflow Steps
1. **`start`** - Manual invocation
2. **`userMessage`** - Check if global podcast data exists and display relevant message
3. **`runFunction`** - Prepare the podcasts array for processing
4. **`userMessage`** - Display the number of podcasts to be processed
5. **`runFunction`** - Process each podcast (download and transcribe)
6. **`downloadVideo`** - For each podcast, download the audio
7. **`transcribeAudio`** - Transcribe the downloaded audio
8. **`userMessage`** - Format transcript with speaker identification and markdown
9. **`runFunction`** - Add the transcript to the podcast data array
10. **`userMessage`** - Format results for user review
11. **`end`** - Store enhanced data in a global variable and inform the user about next steps

### Variables
```typescript
interface TranscriptGeneratorVariables {
  // Input from previous workflow
  global.podcastData: Array<{
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
  }>;
  
  // Processing variables
  currentPodcastIndex: number;          // e.g., 0
  currentPodcast: {                     // Current podcast being processed
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
  };
  audioFilePath: string;                // e.g., "https://example.com/downloaded/audio.mp3"
  rawTranscript: string;                // Raw transcription text (large text string)
  formattedTranscript: string;          // Markdown transcript with speaker identification
  
  // Output for next workflow
  global.podcastsWithTranscripts: Array<{
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
    transcript: string;               // Markdown formatted transcript
  }>;
}
```

### Custom Functions

#### 1. Prepare Podcasts Array
```typescript
// Configuration for runFunction block
{
  functionId: "preparePodcastsArray",
  configuration: {
    globalPodcastData: "{{global.podcastData}}",
    destinationVar: "podcastsArray"
  }
}

// Function inputs/outputs
interface PreparePodcastsArrayInputs {
  globalPodcastData: any;  // The global podcast data from workflow 1
}

interface PreparePodcastsArrayOutputs {
  podcastsArray: Array<any>;  // Copy of the global data for processing
}
```

#### 2. Process Each Podcast
```typescript
// Configuration for runFunction block
{
  functionId: "processPodcast",
  configuration: {
    podcastsArray: "{{podcastsArray}}",
    currentIndex: "{{currentPodcastIndex}}",
    destinationCurrentPodcast: "currentPodcast",
    destinationHasMore: "hasMorePodcasts",
    destinationNextIndex: "currentPodcastIndex"
  },
  transitionType: "dynamic",
  transitions: {
    continue: "downloadPodcast",
    end: "displayResults"
  }
}

// Function inputs/outputs
interface ProcessPodcastInputs {
  podcastsArray: Array<any>;     // Array of podcasts to process
  currentIndex: number;          // Current index in the array
}

interface ProcessPodcastOutputs {
  currentPodcast: any;           // The podcast to process next
  hasMorePodcasts: boolean;      // Whether there are more podcasts to process
  currentPodcastIndex: number;   // Updated index for next iteration
}
```

#### 3. Add Transcript to Podcast Data
```typescript
// Configuration for runFunction block
{
  functionId: "addTranscriptToPodcast",
  configuration: {
    podcastsArray: "{{podcastsArray}}",
    currentIndex: "{{currentPodcastIndex}}",
    transcript: "{{formattedTranscript}}",
    destinationPodcastsArray: "podcastsArray"
  }
}

// Function inputs/outputs
interface AddTranscriptToPodcastInputs {
  podcastsArray: Array<any>;     // Array of podcasts
  currentIndex: number;          // Current podcast index
  transcript: string;            // Formatted transcript to add
}

interface AddTranscriptToPodcastOutputs {
  podcastsArray: Array<any>;     // Updated array with transcript added
}
```

## Workflow 3: Summary Generator

### Invocation
- Manually triggered by pressing the "Run" button
- No launch variables required

### System Prompt Outline
- Define the workflow as a podcast content analyzer
- Specify tasks of extracting key learnings and entities
- Define the expected output format for summaries and entity lists

### Workflow Steps
1. **`start`** - Manual invocation
2. **`userMessage`** - Check if global podcast data with transcripts exists
3. **`runFunction`** - Prepare the podcasts array for processing
4. **`userMessage`** - Display the number of podcasts to be processed
5. **`runFunction`** - Process each podcast (generate summaries and entity lists)
6. **`userMessage`** - Extract key learnings from transcript
7. **`userMessage`** - Extract entities from transcript
8. **`runFunction`** - Add the analysis to the podcast data array
9. **`userMessage`** - Format results for user review
10. **`end`** - Store complete data in a global variable and inform user about next steps

### Variables
```typescript
interface SummaryGeneratorVariables {
  // Input from previous workflow
  global.podcastsWithTranscripts: Array<{
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
    transcript: string;
  }>;
  
  // Processing variables
  podcastsArray: Array<any>;            // Copy of global data for processing
  currentPodcastIndex: number;          // e.g., 0
  currentPodcast: {                     // Current podcast being processed
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
    transcript: string;
  };
  keyLearnings: Array<string>;          // e.g., ["Learning 1", "Learning 2"]
  entities: Array<{                     // e.g., [{"type": "Person", "name": "John Doe"}]
    type: string;                       // e.g., "Person", "Company", "Website"
    name: string;                       // e.g., "John Doe", "Google", "example.com"
  }>;
  
  // Output for next workflow
  global.fullProcessedData: Array<{
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
    transcript: string;
    keyLearnings: Array<string>;
    entities: Array<{
      type: string;
      name: string;
    }>;
  }>;
}
```

### Custom Functions

#### 1. Prepare Podcasts Array (similar to Workflow 2)
```typescript
// Configuration for runFunction block
{
  functionId: "preparePodcastsArray",
  configuration: {
    globalPodcastData: "{{global.podcastsWithTranscripts}}",
    destinationVar: "podcastsArray"
  }
}

// Function inputs/outputs
interface PreparePodcastsArrayInputs {
  globalPodcastData: any;  // The global podcast data from workflow 2
}

interface PreparePodcastsArrayOutputs {
  podcastsArray: Array<any>;  // Copy of the global data for processing
}
```

#### 2. Process Each Podcast (similar to Workflow 2)
```typescript
// Configuration for runFunction block
{
  functionId: "processPodcast",
  configuration: {
    podcastsArray: "{{podcastsArray}}",
    currentIndex: "{{currentPodcastIndex}}",
    destinationCurrentPodcast: "currentPodcast",
    destinationHasMore: "hasMorePodcasts",
    destinationNextIndex: "currentPodcastIndex"
  },
  transitionType: "dynamic",
  transitions: {
    continue: "extractKeyLearnings",
    end: "displayResults"
  }
}

// Function inputs/outputs
interface ProcessPodcastInputs {
  podcastsArray: Array<any>;     // Array of podcasts to process
  currentIndex: number;          // Current index in the array
}

interface ProcessPodcastOutputs {
  currentPodcast: any;           // The podcast to process next
  hasMorePodcasts: boolean;      // Whether there are more podcasts to process
  currentPodcastIndex: number;   // Updated index for next iteration
}
```

#### 3. Add Analysis to Podcast Data
```typescript
// Configuration for runFunction block
{
  functionId: "addAnalysisToPodcast",
  configuration: {
    podcastsArray: "{{podcastsArray}}",
    currentIndex: "{{currentPodcastIndex}}",
    keyLearnings: "{{keyLearnings}}",
    entities: "{{entities}}",
    destinationPodcastsArray: "podcastsArray"
  }
}

// Function inputs/outputs
interface AddAnalysisToPodcastInputs {
  podcastsArray: Array<any>;     // Array of podcasts
  currentIndex: number;          // Current podcast index
  keyLearnings: Array<string>;   // Key learnings to add
  entities: Array<any>;          // Entities to add
}

interface AddAnalysisToPodcastOutputs {
  podcastsArray: Array<any>;     // Updated array with analysis added
}
```

## Workflow 4: HTML Generator

### Invocation
- Manually triggered by pressing the "Run" button
- No launch variables required

### System Prompt Outline
- Define the workflow as an HTML generator for podcast summaries
- Specify the format for the summary page and transcript pages
- Define navigation between pages and layout requirements

### Workflow Steps
1. **`start`** - Manual invocation
2. **`userMessage`** - Check if global fully processed podcast data exists
3. **`runFunction`** - Prepare the data for HTML generation
4. **`generatePdf`** - Generate the summary HTML page
5. **`runFunction`** - Generate individual transcript HTML pages
6. **`userMessage`** - Format results and provide links to HTML pages
7. **`end`** - Display success message with links to access the generated pages

### Variables
```typescript
interface HtmlGeneratorVariables {
  // Input from previous workflow
  global.fullProcessedData: Array<{
    episodeNumber: string;
    date: string;
    title: string;
    audioUrl: string;
    transcript: string;
    keyLearnings: Array<string>;
    entities: Array<{
      type: string;
      name: string;
    }>;
  }>;
  
  // Processing variables
  podcastsForHtml: Array<any>;          // Formatted data for HTML generation
  summaryHtmlSource: string;            // HTML template for summary page
  summaryHtmlUrl: string;               // URL of generated summary page
  transcriptPagesUrls: Array<{          // URLs of transcript pages
    episodeNumber: string;
    url: string;
  }>;
  
  // Output variables for final display
  finalOutputLinks: string;             // HTML with links to all pages
}
```

### Custom Functions

#### 1. Prepare HTML Data
```typescript
// Configuration for runFunction block
{
  functionId: "prepareHtmlData",
  configuration: {
    globalFullProcessedData: "{{global.fullProcessedData}}",
    destinationVar: "podcastsForHtml"
  }
}

// Function inputs/outputs
interface PrepareHtmlDataInputs {
  globalFullProcessedData: any;  // The global fully processed podcast data
}

interface PrepareHtmlDataOutputs {
  podcastsForHtml: Array<any>;   // Formatted data ready for HTML generation
}
```

#### 2. Generate Transcript Pages
```typescript
// Configuration for runFunction block
{
  functionId: "generateTranscriptPages",
  configuration: {
    podcastsData: "{{podcastsForHtml}}",
    summaryPageUrl: "{{summaryHtmlUrl}}",
    destinationTranscriptUrls: "transcriptPagesUrls",
    destinationFinalOutput: "finalOutputLinks"
  }
}

// Function inputs/outputs
interface GenerateTranscriptPagesInputs {
  podcastsData: Array<any>;      // Podcast data with transcripts
  summaryPageUrl: string;        // URL to the summary page for back navigation
}

interface GenerateTranscriptPagesOutputs {
  transcriptPagesUrls: Array<{   // URLs of generated transcript pages
    episodeNumber: string;
    url: string;
  }>;
  finalOutputLinks: string;      // HTML with links to all generated pages
}
```

## Specific Notes

1. The podcast website URL is explicitly provided: https://ggapps.co.uk/podcast/
2. The podcast audio URLs need to be extracted from the `iframe` head as specified, e.g., "https://traffic.libsyn.com/secure/8e658e4a-8560-4204-9ebd-6f1db1b6ade2/Ep1.mp3"
3. Speaker identification is required in the transcripts
4. The HTML output should include:
   - A summary page showing all podcast metadata, key learnings, and entities
   - Links to individual transcript pages
   - Back navigation from transcript pages to the summary page
5. The workflows are designed to be run sequentially, with each workflow building on the data from the previous one
