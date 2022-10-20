import {openDB} from 'idb';

class Database {
  constructor() {
    this.database = null;
  }

  initialize() {
    if (!('indexedDB' in window)) {
      // todo
      console.log('This browser doesn\'t support IndexedDB.');

      return;
    }

    this.database = openDB('na_database', 1, {
      upgrade(database, oldVersion, newVersion) {
        if (newVersion === 1) {
          const episodeStore = database.createObjectStore('na_episode', {keyPath: 'code'});
          episodeStore.createIndex('title', 'title');
          episodeStore.createIndex('src', 'src');
          episodeStore.createIndex('duration', 'duration');
          episodeStore.createIndex('publishedAt', 'publishedAt');
          episodeStore.createIndex('url', 'url');
          episodeStore.createIndex('cover', 'cover');
          episodeStore.createIndex('transcript', 'transcript');
          episodeStore.createIndex('playbackPosition', 'playbackPosition');
          episodeStore.createIndex('playbackFinished', 'playbackFinished');
          episodeStore.createIndex('playbackSavedAt', 'playbackSavedAt');
        }
      }
    });
  }

  find(entity, key) {
    return this.database.then(database => {
      const transaction = database.transaction(`na_${entity}`, 'readonly');
      const objectStore = transaction.objectStore(`na_${entity}`);

      return objectStore.get(key);
    });
  }

  all(entity) {
    return this.database.then(database => {
      const transaction = database.transaction(`na_${entity}`, 'readonly');
      const objectStore = transaction.objectStore(`na_${entity}`);

      return objectStore.getAll();
    });
  }

  async persist(entity, data, overwrite = false) {
    const objectStore = (await this.database)
      .transaction(`na_${entity}`, 'readwrite')
      .objectStore(`na_${entity}`);

    if (overwrite) {
      return objectStore.put(data);
    } else {
      return objectStore.add(data);
    }
  }
}

const naStorage = window.naStorage = window.naStorage || new Database();

export default naStorage;
