import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
import joblib

def train_rf(df_features, df_labels, out_model_path='model_rf.pkl'):
    X = df_features.values
    y = df_labels.values
    Xtr, Xte, ytr, yte = train_test_split(X, y, test_size=0.2, random_state=42)
    clf = RandomForestClassifier(n_estimators=200, max_depth=12, random_state=42)
    clf.fit(Xtr, ytr)
    print("Train score:", clf.score(Xtr, ytr), "Test score:", clf.score(Xte, yte))
    joblib.dump(clf, out_model_path)
    return clf

def predict(clf_path, features_df):
    clf = joblib.load(clf_path)
    preds = clf.predict(features_df.values)
    return preds
