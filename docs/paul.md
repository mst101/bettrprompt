I want to create a summary of all podcasts at https://ggapps.co.uk/podcast/

Important! Use multiple workflows when appropriate.

Extract:
- Episode number
- Date
- Podcast title
- Podcast URL

N.B. The podcast URL is in the `head` of each `iframe` e.g. "https://traffic.libsyn.com/secure/8e658e4a-8560-4204-9ebd-6f1db1b6ade2/Ep1.mp3"

Then for each podcast, visit the podcast URL and extract a transcript of the audio and format it in markdown. Make sure you describe which person is speaking. Add this transcript to the array of podcasts.

Then for each podcast, create a summary of:
- key learnings
- entities e.g. people, companies, websites etc.

...and add these arrays to the podcast array.

Then create an HTML page that displays all of this summary information (except for the full transcript) and add a link to the transcript, so that I can view it in a separate page (and then click back from there to the summary).

===

I understand you want to build a MindStudio agent that scrapes podcast information, extracts transcripts, creates summaries, and generates an HTML display page. This is a good fit for MindStudio's capabilities, though it has some technical challenges we'll need to address.

Let me break down your requirements:

Scrape podcast listings from the specified website
Extract metadata (episode number, date, title, URL)
Download and transcribe each podcast audio file
Format transcripts with speaker identification
Create AI-generated summaries of key points and entities
Build an HTML interface with summaries and links to transcripts
A few considerations:

Speaker identification in transcription can be challenging without training data, but we'll use AI to make best guesses
Processing multiple podcasts with transcription will take some time
We'll use multiple sub-workflows as requested, with a main orchestration flow
This is definitely doable in MindStudio. I recommend structuring it with:

Main workflow to orchestrate the process and generate the HTML
Transcript sub-workflow to handle audio processing
Summary sub-workflow to analyze transcripts
Would you like me to draft a detailed spec for this agent?

===

# Podcast Archive Creator

An agent that scrapes podcast information from a website, transcribes audio files, generates summaries, and creates a searchable HTML archive with episode details and transcripts.

## Invocation Method

This agent will be invoked manually by the user, who will press a button to start the process. No specific inputs are needed from the user as the podcast URL is hardcoded into the workflow.

## System Prompt Outline

The system prompt should define the agent as a podcast data processor with expertise in:
- Web scraping and data extraction
- Audio transcription and processing
- Content summarization and entity extraction
- HTML generation and presentation

It should emphasize the agent's capabilities in analyzing spoken content, identifying speakers, and extracting key information from conversations.

## Workflow Structure

The workflow will be divided into three sub-workflows:

### Main.flow
1. `start` - Manual invocation
2. `setRunTitle` - Set the title to "Podcast Archive Creator"
3. `scrapeUrl` - Scrape the podcast listing page (https://ggapps.co.uk/podcast/)
4. `userMessage` - Extract podcast metadata (episode number, date, title) from the scraped page
5. `userMessage` - Extract iframe URLs from the scraped page
6. Loop through each podcast:
   - `jump` to ProcessPodcast.flow for each podcast
   - Store results in the podcasts array
7. `generatePdf` - Generate main HTML output page
8. For each podcast transcript:
   - `generatePdf` - Generate individual transcript pages
9. `userMessage` - Notify user of completion with links
10. `end` - Terminate the workflow

### ProcessPodcast.flow
1. `start` - Accepts podcast metadata as launch variables
2. `scrapeUrl` - Scrape the iframe page to extract the audio URL from the head
3. `downloadVideo` - Download the podcast audio file
4. `transcribeAudio` - Transcribe the audio file
5. `userMessage` - Format transcript with speaker identification
6. `jump` to SummarizePodcast.flow
7. `end` - Return the processed podcast data

### SummarizePodcast.flow
1. `start` - Accepts transcript as a launch variable
2. `userMessage` - Extract key learnings from the transcript
3. `userMessage` - Identify entities mentioned in the transcript
4. `end` - Return the summary data

## Variables Definition

```typescript
// Main.flow variables
interface MainWorkflowVariables {
  // Scraping variables
  scrapedPage: string;                // Raw HTML content of podcast listing page
  podcastMetadata: PodcastMetadata[]; // Array of extracted podcast metadata
  iframeUrls: string[];               // Array of iframe URLs
  
  // Processing results
  podcasts: ProcessedPodcast[];       // Array of fully processed podcast data
  
  // Output variables
  mainHtmlUrl: string;                // URL of generated main HTML page
  transcriptHtmlUrls: string[];       // Array of URLs for transcript pages
}

// ProcessPodcast.flow variables
interface ProcessPodcastVariables {
  // Launch variables
  episodeNumber: string;              // Example: "Episode 42"
  date: string;                       // Example: "January 15, 2023"
  title: string;                      // Example: "Interview with John Doe"
  iframeUrl: string;                  // Example: "https://example.com/iframe/123"
  
  // Processing variables
  iframeContent: string;              // Scraped content of the iframe
  audioUrl: string;                   // Example: "https://traffic.libsyn.com/secure/8e658e4a-8560-4204-9ebd-6f1db1b6ade2/Ep1.mp3"
  audioFile: string;                  // URL to downloaded audio file
  rawTranscript: string;              // Raw transcript from audio transcription
  formattedTranscript: string;        // Transcript with speaker identification in markdown
  
  // Result
  processedPodcast: ProcessedPodcast; // Complete podcast data
}

// SummarizePodcast.flow variables
interface SummarizePodcastVariables {
  // Launch variables
  transcript: string;                 // Formatted transcript in markdown
  
  // Summary variables
  keyLearnings: string;               // Summary of key points (500-1000 characters)
  entities: Entity[];                 // List of entities mentioned
  
  // Result
  summary: PodcastSummary;            // Complete summary data
}

// Shared data structures
interface PodcastMetadata {
  episodeNumber: string;              // Example: "Episode 42"
  date: string;                       // Example: "January 15, 2023"
  title: string;                      // Example: "Interview with John Doe"
}

interface Entity {
  type: string;                       // Example: "person", "company", "website"
  name: string;                       // Example: "John Smith"
  mentions: number;                   // Example: 3
}

interface PodcastSummary {
  keyLearnings: string;               // Summary of key points
  entities: Entity[];                 // List of entities
}

interface ProcessedPodcast {
  episodeNumber: string;              // Example: "Episode 42"
  date: string;                       // Example: "January 15, 2023"
  title: string;                      // Example: "Interview with John Doe"
  audioUrl: string;                   // Example: "https://traffic.libsyn.com/secure/8e658e4a-8560-4204-9ebd-6f1db1b6ade2/Ep1.mp3"
  transcript: string;                 // Formatted transcript with speaker identification
  summary: PodcastSummary;            // Summary data
  transcriptHtmlUrl: string;          // URL to the transcript HTML page
}
```

## Custom Functions

### Extract Audio URL from Iframe

```typescript
// Configuration for runFunction block
{
  "functionName": "extractAudioUrlFromIframe",
  "configuration": {
    "iframeContent": "{{iframeContent}}",
    "outputVariable": "audioUrl"
  }
}

// Function configuration interface
interface ExtractAudioUrlFromIframeConfig {
  iframeContent: string;   // The HTML content of the iframe
  outputVariable: string;  // The name of the variable to store the result
}

// Function output
// Sets a variable with the name specified in outputVariable containing the extracted audio URL
// Example: "https://traffic.libsyn.com/secure/8e658e4a-8560-4204-9ebd-6f1db1b6ade2/Ep1.mp3"
```

This function will:
1. Parse the HTML content of the iframe
2. Extract the audio URL from the head section
3. Set the specified output variable with the extracted URL

## Specific Notes

- The podcast listing page is at `https://ggapps.co.uk/podcast/`
- The podcast audio URLs are in the format `https://traffic.libsyn.com/secure/8e658e4a-8560-4204-9ebd-6f1db1b6ade2/Ep1.mp3` and are located in the `head` section of each `iframe`
- The transcript should be formatted in markdown with speaker identification
- The HTML output should include all podcast information except the full transcript, with links to view transcripts on separate pages
- The transcript pages should include a link back to the main summary page
